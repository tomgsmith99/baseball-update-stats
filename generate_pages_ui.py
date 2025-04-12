
from generate_pages import generate_page

import argparse

THIS_SEASON = 2025

##########################
def main():

    parser = argparse.ArgumentParser(description="generate static page(s)")

    parser.add_argument("--section", type=str, required=True, help="The section of the site to generate")

    args = parser.parse_args()

    print(f"Generating page {args.section}")

    generate_page(THIS_SEASON, "trade")

if __name__ == "__main__":
    main()
