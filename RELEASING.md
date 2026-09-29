Releasing
=========

Packagist learns about a new version from the tag and fetches the code from GitHub.
Nothing is uploaded, so there is no publish workflow and no credentials.

1. Update `$SEGMENT_VERSION` in `lib/Version.php`.
2. In `HISTORY.md`, change the `Unreleased` heading to `X.Y.Z / YYYY-MM-DD`.
3. Open a PR and merge it to `master`.
4. Tag the merged commit — no `v` prefix:

   ```
   git tag X.Y.Z && git push origin X.Y.Z
   ```

5. Confirm at https://packagist.org/packages/segmentio/analytics-php
