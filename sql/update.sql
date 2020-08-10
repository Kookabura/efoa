
/* Set the database to change */
USE efoa;

/* Set the right prefix -- in this example it is modx_ */
ALTER TABLE modx_user_attributes add `twitter` varchar(255) NOT NULL,
add `facebook` varchar(255) NOT NULL,
add `instagram` varchar(255) NOT NULL,
add `pinterest` varchar(255) NOT NULL,
add `linkedin` varchar(255) NOT NULL,
add `youtube` varchar(255) NOT NULL,
add `shop` varchar(255) NOT NULL,
add `discipline` text NOT NULL,
add `biography` text NOT NULL,
add `cv` text NOT NULL,
add `highlight_image` varchar(255) NOT NULL,
add `summary` text NOT NULL,
add `openhouse_host` tinyint(1) NOT NULL DEFAULT '0',
add `openhouse_exhibiting` tinyint(1) NOT NULL DEFAULT '0',
add `openhouse_number` int(10) NOT NULL DEFAULT '0';


/* Below is the MODX default for this table before the above command is issued */
/*
+------------------+---------------------+------+-----+---------+----------------+
| Field            | Type                | Null | Key | Default | Extra          |
+------------------+---------------------+------+-----+---------+----------------+
| id               | int(10) unsigned    | NO   | PRI | NULL    | auto_increment |
| internalKey      | int(10)             | NO   | UNI | NULL    |                |
| fullname         | varchar(100)        | NO   |     |         |                |
| email            | varchar(100)        | NO   |     |         |                |
| phone            | varchar(100)        | NO   |     |         |                |
| mobilephone      | varchar(100)        | NO   |     |         |                |
| blocked          | tinyint(1) unsigned | NO   |     | 0       |                |
| blockeduntil     | int(11)             | NO   |     | 0       |                |
| blockedafter     | int(11)             | NO   |     | 0       |                |
| logincount       | int(11)             | NO   |     | 0       |                |
| lastlogin        | int(11)             | NO   |     | 0       |                |
| thislogin        | int(11)             | NO   |     | 0       |                |
| failedlogincount | int(10)             | NO   |     | 0       |                |
| sessionid        | varchar(100)        | NO   |     |         |                |
| dob              | int(10)             | NO   |     | 0       |                |
| gender           | int(1)              | NO   |     | 0       |                |
| address          | text                | NO   |     | NULL    |                |
| country          | varchar(191)        | NO   |     |         |                |
| city             | varchar(191)        | NO   |     |         |                |
| state            | varchar(25)         | NO   |     |         |                |
| zip              | varchar(25)         | NO   |     |         |                |
| fax              | varchar(100)        | NO   |     |         |                |
| photo            | varchar(191)        | NO   |     |         |                |
| comment          | text                | NO   |     | NULL    |                |
| website          | varchar(191)        | NO   |     |         |                |
| extended         | text                | YES  |     | NULL    |                |
+------------------+---------------------+------+-----+---------+----------------+
*/