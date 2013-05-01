import argparse
import json
import os
import random
import sys
import tempfile
import dateutil.parser as date_parser
from analytics.client import Client


clients = {}


def process_file(filename):
    '''Processes the log file line by line'''

    print 'Processing file', filename

    with open(filename) as f:
        for line in f:
            process_line(line)


def process_line(line):
    '''Tracks a single line by parsing its json and tracking'''
    line = line.strip()
    try:
        contents = json.loads(line)
        secret, action = contents['secret'], contents['action']

        if action == 'track':
            track(secret, contents)
        elif action == 'identify':
            identify(secret, contents)
        elif action == 'alias':
            alias(secret, contents)
        else:
            print 'Error processing call'
    except Exception as e:
        print 'Malformed JSON', e


def get_client(secret):
    '''Returns or creates a new client by the secret'''

    if secret not in clients:
        clients[secret] = Client(secret)

    return clients[secret]


def track(secret, contents):
    '''Tracks an event'''

    client = get_client(secret)

    body = shared_properties(contents)
    body['event'] = contents['event']

    if contents['properties'] is not None:
        body['properties'] = contents['properties']

    return client.track(**body)


def identify(secret, contents):
    '''Identifies a user with traits'''

    client = get_client(secret)

    body = shared_properties(contents)

    if contents['traits'] is not None:
        body['traits'] = contents['traits']

    return client.identify(**body)


def alias(secret, contents):
    '''Aliases from one user to the other'''

    client = get_client(secret)
    body = {
        'from_id':      contents['from'],
        'to_id':        contents['to'],
        'timestamp': date_parser.parse(contents['timestamp']),
        'context':   contents['context']
    }

    return client.alias(**body)


def shared_properties(contents):
    '''Returns the common properties between track and identify'''

    return {
        'user_id':   contents['user_id'],
        'timestamp': date_parser.parse(contents['timestamp']),
        'context':   contents['context']
    }


'''

    Processes the analytics.log file.

    Requires: analytics-python (pip install analytics-python)

    Usage:
        python file_reader.py [--file /path/to/analytics.log]
'''
if __name__ == '__main__':

    # Parse the file arguments
    parser = argparse.ArgumentParser(
                description='Read from the analytics.log file')

    parser.add_argument('--file')
    args = parser.parse_args()

    default_file = os.path.join(tempfile.gettempdir(), 'analytics.log')

    filename = args.file or default_file

    # Use the same directory for processing the log as where it is stored.
    # This prevents "Invalid cross-device link" errors.
    processing_dir = os.path.dirname(filename)

    processing_filename = os.path.join(processing_dir,
                                'analytics-%d.log' % random.randint(0, 1000))

    if not os.path.exists(filename):
        print 'Error: The filename you specified doesn\'t exist: ', filename
        sys.exit(1)

    # Move the file so that we don't read from constantly appended file.
    # Then process it.
    os.rename(filename, processing_filename)
    process_file(processing_filename)
    os.unlink(processing_filename)

    for client in clients.values():
        client.flush(async=False)

    print 'Finished uploading analytics data'
