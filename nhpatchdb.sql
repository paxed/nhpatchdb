-- tables for NetHack Database
BEGIN;

-- nethack variant names/versions
CREATE TABLE variant (
	id	serial NOT NULL PRIMARY KEY,
	name	character varying NOT NULL	-- name of the variant, eg. "NetHack 3.4.3"
);

COPY variant (name) FROM stdin;
NetHack 3.4.3
NetHack 3.4.2
NetHack 3.4.1
NetHack 3.4.0
NetHack 3.3.1
NetHack 3.3.0
\.


-- patch data
CREATE TABLE patches (
	id	serial NOT NULL PRIMARY KEY,
	pname	character varying NOT NULL,	-- name of the patch
	ver	character varying NOT NULL,	-- version # of the patch
	author	character varying NOT NULL,	-- who is the author
	file	character varying,		-- patch filename
	fsize	integer,			-- size of the diff, in Kb
	nhfor	integer REFERENCES variant,	-- for what [NetHack/Slash'Em] version?
	descs	TEXT,				-- short description
	descl	TEXT,				-- long description
	dlurl	character varying,		-- URL to download the patch directly
	url	character varying,		-- URL to the homepage
	xinfo	TEXT,				-- extra info, for admins
	rating	integer,			-- user rating, calculated from comments
	queue	boolean,			-- is this waiting for admin to accept it?
	added	timestamp,			-- when this was added?
	changed	timestamp,			-- when was this last changed?
	fdata	TEXT,				-- patch file contents
	localdl	boolean,			-- is local download allowed?
	patchref integer			-- what patch this one refers to (=this one is an update to patchref)
);

-- comment data
CREATE TABLE comments (
	id		serial NOT NULL PRIMARY KEY,
	username	character varying,		-- name of the user
	patch		integer REFERENCES patches,	-- points to patches.id
	text		text,				-- comment text
	score		integer,			-- score for the patch (0-5)
	added		timestamp			-- when was this comment added?
);

COMMIT;