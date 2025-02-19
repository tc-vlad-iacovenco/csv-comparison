# CSV Comparison Tool

Compares CSV files between two directories (recursively).

## Usage

```bash
# Build container
docker-compose build

# Build container
docker-compose up -d

# Run comparison
docker-compose run --rm php php compare_csv.php dir1 dir2
```

Place your CSV files under `./reports/dir1` and `./reports/dir2`.