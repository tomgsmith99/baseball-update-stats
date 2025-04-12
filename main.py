import time

from generate_pages import generate_home_page, generate_page
from owner import Owner
from player import Player
from utils.conn_psql import PostgreSQLDatabase, fetch_results

##################################
THIS_SEASON = 2025
SALARY_CAP = 13000

PAUSE_LENGTH = 0.2
##################################

def update_owner_x_player_table(season):
    # Query to retrieve distinct player IDs along with last and first names, ordered as desired.
    query = """
        SELECT sub.player_id, sub.last_name, sub.first_name
        FROM (
            SELECT DISTINCT oxp.player_id, p.last_name, p.first_name
            FROM owner_x_player AS oxp
            LEFT JOIN player AS p ON oxp.player_id = p.id
            WHERE oxp.season = %s
        ) AS sub
        ORDER BY sub.last_name, sub.first_name;
    """
    values = (season,)
    results = fetch_results(query, values)

    if not results:
        print(f"No distinct player IDs found for season {season}.")
        return

    player_count = len(results)
    print(f"{player_count} distinct player IDs in the {season} season:")

    # Open a single connection for all updates
    with PostgreSQLDatabase() as db:
        for i, row in enumerate(results, start=1):
            player_id = row[0]
            player = Player(player_id)
            print(f"Updating player {i} of {player_count}: {player.display_name}")

            # Calculate the points for the season
            points = player.get_points(season)

            # Update the points for this player in the owner_x_player table
            update_query = """
                UPDATE owner_x_player 
                SET points = %s 
                WHERE season = %s AND player_id = %s 
                  AND start_date = 0 AND bench_date = 0;
            """
            update_values = (points, season, player_id)
            db.execute_query(update_query, update_values)

def update_owners(season):

    query = """
        SELECT id FROM owner WHERE id IN (SELECT id FROM owner_x_season WHERE season = %s) ORDER BY owner.last_name, owner.first_name, owner.suffix;
    """

    results = fetch_results(query, (season,))

    owner_count = len(results)

    if results:
        print(f"{owner_count} owners found for season {season}.")
    else:
        print(f"No owners found for season {season}.")
        exit()

    i = 0

    for row in results:

        i += 1

        owner = Owner(row[0])

        print(f"Updating owner {i} of {owner_count}...")

        print("\n#############################")

        print(owner.nickname)

        owner.update_stats(season)

def update_place(season):

    query = """
        SELECT id, points
        FROM owner_x_points_x_day_x_season
        WHERE season = %s
        AND day = (
            SELECT MAX(day)
            FROM owner_x_points_x_day_x_season
            WHERE season = %s
        )
        ORDER BY points DESC;
    """

    results = fetch_results(query, (season, season))

    if results:
        print(f"{len(results)} owners found for season {season}.")
    else:
        print(f"No owners found for season {season}.")
        exit()

    i = 0

    for row in results:

        i += 1

        owner = Owner(row[0])

        print(f"Updating place for owner {i} of {len(results)}...")

        print("\n#############################")

        print(owner.nickname)

        owner.update_place(i, season)

def update_players(season):

    query = """
        SELECT id FROM player WHERE id IN (SELECT id FROM player_x_season WHERE season = %s) ORDER BY player.last_name, player.first_name;
    """

    results = fetch_results(query, (season,))

    player_count = len(results)

    if results:
        print(f"{player_count} players found for season {season}.")
    else:
        print(f"No players found for season {season}.")
        exit()

    i = 1

    for row in results:

        print(f"Updating player {i} of {player_count}...")

        i += 1

        player = Player(row[0])

        print("\n#############################")

        print(player.display_name)

        player.update_stats(season)

        print("pausing...")

        time.sleep(PAUSE_LENGTH)

######################################################
# ✅ MAIN FUNCTION

def main():

    print("Updating stats...")

    update_players(THIS_SEASON)

    update_owner_x_player_table(THIS_SEASON)

    update_owners(THIS_SEASON)

    update_place(THIS_SEASON)

    generate_page(THIS_SEASON, "home")

    generate_page(THIS_SEASON, "trade")

    #################################################################

    exit()

if __name__ == "__main__":
    main()
