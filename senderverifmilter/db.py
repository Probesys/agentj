from os import environ
from importlib import import_module
from dbutils.pooled_db import PooledDB


class DbConnectionPool:

    def __init__(self):
        self.driver = 'pymysql'
        self.backend = import_module(self.driver)
        db_config = {
            'host': environ['DB_HOST'],
            'user': environ['DB_USER'],
            'password': environ['DB_PASSWORD'],
            'database': environ['DB_NAME'],
            'port': 3306,
            'cursorclass': self.backend.cursors.DictCursor
        }

        self.pool = PooledDB(
            creator=self.backend,
            # maxconnections=int(conf.get('DB_POOL_MAXCONNECTIONS', 20)),
            **db_config
        )

    def connection(self):
        connection = self.pool.connection()
        return connection
