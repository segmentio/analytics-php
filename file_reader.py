import analytics
import argparse
import json
import os
import random
import sys
import tempfile
import dateutil.parser as date_parser


clients = {}


def process_file(filename):
    print 'Processing file', filename

    with open(filename) as f:
        for line in f:
            process_line(line)


def process_line(line):
    line = line.strip()
    try:
        contents = json.loads(line)
        secret, action = contents['secret'], contents['action']

        if action == 'track':
            track(secret, contents)
        elif action == 'identify':
            identify(secret, contents)
        else:
            print 'Error processing call'
    except Exception as e:
        print 'Malformed JSON', e


def get_client(secret):
    if secret not in clients:
        clients[secret] = analytics.Client(secret)

    return clients[secret]


def track(secret, contents):
    client = get_client(secret)

    body = shared_properties(contents)
    body['event'] = contents['event']

    if contents['properties'] is not None:
        body['properties'] = contents['properties']

    return client.track(**body)


def identify(secret, contents):
    client = get_client(secret)

    body = shared_properties(contents)

    if contents['traits'] is not None:
        body['traits'] = contents['traits']

    return client.identify(**body)


def shared_properties(contents):
    return {
        'user_id':   contents['user_id'],
        'timestamp': date_parser.parse(contents['timestamp']),
        'context':   contents['context']
    }


if __name__ == '__main__':

    parser = argparse.ArgumentParser(
                description='Read from the analytics.log file')

    parser.add_argument('--file')
    args = parser.parse_args()

    default_file = os.path.join(tempfile.gettempdir(), 'analytics.log')
    processing_filename = os.path.join(tempfile.gettempdir(),
                                'analytics-%d.log' % random.randint(0, 1000))

    filename = args.file or default_file

    if not os.path.exists(filename):
        print 'Error: The filename you specified doesn\'t exist: ', filename
        sys.exit(1)

    os.rename(filename, processing_filename)
    process_file(processing_filename)
    os.unlink(processing_filename)

    for client in clients.values():
        client.flush()

    print 'Finished uploading analytics data'
