
from generate_pages import generate_page, generate_section

import argparse

THIS_SEASON = 2025

valid_pages = ['home', 'make_a_trade', 'players']
valid_sections = ['players']

##########################
def main():

    parser = argparse.ArgumentParser(description="generate static page(s)")

    group = parser.add_mutually_exclusive_group(required=True)

    group.add_argument(
        "--section",
        type=str,
        choices=valid_sections,
        help=f"The section of the site to generate. Valid options: {', '.join(valid_sections)}"
    )

    group.add_argument(
        "--page",
        type=str,
        choices=valid_pages,
        help=f"The page of the site to generate. Valid options: {', '.join(valid_pages)}"
    )

    args = parser.parse_args()

    if args.section:
        print(f"Generating section: {args.section}")
        generate_section(THIS_SEASON, args.section)

    elif args.page:
        print(f"Generating page: {args.page}")
        generate_page(THIS_SEASON, args.page)

if __name__ == "__main__":
    main()
