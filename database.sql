CREATE TABLE IF NOT EXISTS urls (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at timestamp NOT NULL
);

CREATE TABLE IF NOT EXISTS url_checks (
    id SERIAL PRIMARY KEY,
    url_id int REFERENCES urls (id) ON DELETE CASCADE NOT NULL,
    status_code varchar(55),
    h1 varchar(255),
    title varchar(255),
    description text,
    created_at timestamp NOT NULL
);