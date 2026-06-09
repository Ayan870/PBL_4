import aiomysql
import asyncio
import os

# Database configuration (matching config/db.php)
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "db": "projecxia",
    "port": 3307,
    "autocommit": True
}

async def get_db_pool():
    try:
        pool = await aiomysql.create_pool(**DB_CONFIG)
        return pool
    except Exception as e:
        # Fallback to 3306 if 3307 fails (opposite of before)
        print(f"Failed to connect to 3307: {e}. Trying 3306...")
        DB_CONFIG["port"] = 3306
        try:
            pool = await aiomysql.create_pool(**DB_CONFIG)
            return pool
        except Exception as e2:
            print(f"Final connection failure: {e2}")
            raise e2

class Database:
    _pool = None

    @classmethod
    async def get_pool(cls):
        if cls._pool is None:
            cls._pool = await get_db_pool()
        return cls._pool

    @classmethod
    async def execute(cls, query, params=None):
        pool = await cls.get_pool()
        async with pool.acquire() as conn:
            async with conn.cursor(aiomysql.DictCursor) as cur:
                await cur.execute(query, params)
                return await cur.fetchall()

    @classmethod
    async def execute_commit(cls, query, params=None):
        pool = await cls.get_pool()
        async with pool.acquire() as conn:
            async with conn.cursor() as cur:
                await cur.execute(query, params)
                last_id = cur.lastrowid
                await conn.commit()
                return last_id
