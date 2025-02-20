# CSV Comparison Tool

Compares CSV files between two directories recursively, with support for flexible or strict row ordering.

## Features

- Recursive directory comparison
- Flexible row ordering comparison (default)
- Optional strict row ordering comparison
- Detailed difference reporting
- Docker support

## Usage

```bash
# Build container
docker-compose build

# Start container
docker-compose up -d

# Run comparison with flexible ordering (default)
docker-compose run --rm php php compare_csv.php dir1 dir2

# Run comparison with strict row ordering
docker-compose run --rm php php compare_csv.php dir1 dir2 --strict-ordering