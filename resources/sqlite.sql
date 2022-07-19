-- #!sqlite
-- # { init
-- #    { load
CREATE TABLE IF NOT EXISTS players (
    username VARCHAR(16),
    date TEXT
)
-- #    }

-- #    { view
SELECT * FROM players
-- #    }

-- #    { create
-- #        :username string
-- #        :date string
INSERT INTO players (username, date)
VALUES (:username, :date)
-- #    }

-- #    { update
-- #        :username string
-- #        :date ?string
UPDATE players
SET date=:date
WHERE username = :username
-- #    }

-- #    { reset
-- #        :username string
UPDATE players
SET date=''
WHERE username = :username
-- #    }

-- # }
