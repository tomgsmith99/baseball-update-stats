
from generate_pages import generate_page

import argparse

THIS_SEASON = 2025

##########################
def main():

    valid_sections = ['home', 'trades']

    parser = argparse.ArgumentParser(description="generate static page(s)")

    parser.add_argument(
        "--section",
        type=str,
        choices=valid_sections,
        required=True,
        help=f"The section of the site to generate. Valid options: {', '.join(valid_sections)}"
    )
    
    args = parser.parse_args()

    print(f"Generating page {args.section}")

    generate_page(THIS_SEASON, args.section)

if __name__ == "__main__":
    main()
