- DOC: Update README.

- FIX: Add error handling for glob(). glob() returns false for an error, which
  causes the foreach to raise invalid argument warnings.
