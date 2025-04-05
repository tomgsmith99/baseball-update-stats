import psycopg2
import os
from psycopg2 import OperationalError
from psycopg2.extras import DictCursor  # ✅ Import DictCursor for dictionary results
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

class PostgreSQLDatabase:
    """PostgreSQL Database Connection and Query Execution"""

    def __init__(self):
        """Initialize database connection parameters"""
        self.host = os.getenv("psql_host")
        self.database = os.getenv("psql_database")
        self.user = os.getenv("psql_user")
        self.password = os.getenv("psql_password")
        self.port = os.getenv("psql_port")

        self.connection = None
        self.cursor = None

    def connect(self):
        """Establish connection to PostgreSQL database"""
        # try:
        #     self.connection = psycopg2.connect(
        #         host=self.host,
        #         database=self.database,
        #         user=self.user,
        #         password=self.password,
        #         port=self.port
        #     )
        #     self.cursor = self.connection.cursor(cursor_factory=DictCursor)  # ✅ Use DictCursor
        # except OperationalError as e:
        #     print(f"❌ Connection failed: {e}")
        #     self.connection = None

        try:
            self.connection = psycopg2.connect(
                host=self.host,
                database=self.database,
                user=self.user,
                password=self.password,
                port=self.port,
                sslmode="verify-full",
                sslrootcert="/Users/tomgsmith99/.ssh/global-bundle.pem"  # Use the correct absolute path
            )
            self.cursor = self.connection.cursor(cursor_factory=DictCursor)  # ✅ Use DictCursor
        except OperationalError as e:
            print(f"❌ Connection failed: {e}")
            self.connection = None

    def execute_query(self, query, values=None):
        """Execute a query and commit changes"""
        if self.connection:
            try:
                self.cursor.execute(query, values if values else ())
                self.connection.commit()
            except Exception as e:
                print(f"❌ Query execution failed: {e}")
                exit()

    def get_row(self, query, values=None):
        """Execute a query and return a single row as a dictionary"""
        if self.connection:
            try:
                self.cursor.execute(query, values if values else ())
                return self.cursor.fetchone()  # ✅ Returns a dict instead of tuple
            except Exception as e:
                print(f"❌ Query failed: {e}")
                return None

    def get_rows(self, query, values=None):
        """Execute a query and return all rows as a list of dictionaries"""
        if self.connection:
            try:
                self.cursor.execute(query, values if values else ())
                return self.cursor.fetchall()  # ✅ Returns list of dicts
            except Exception as e:
                print(f"❌ Query failed: {e}")
                return None

    def close(self):
        """Close the database connection"""
        if self.connection:
            self.cursor.close()
            self.connection.close()

    def __enter__(self):
        """Enable usage with 'with' statements"""
        self.connect()
        return self

    def __exit__(self, exc_type, exc_value, traceback):
        """Ensure connection closes after 'with' block"""
        self.close()

# ✅ Explicitly define exports
__all__ = ["PostgreSQLDatabase"]