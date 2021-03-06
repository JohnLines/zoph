#
# Zoph 0.5 -> 0.6 update
#
# This file is part of Zoph.
#
# Zoph is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# Zoph is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with Zoph; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

ALTER TABLE zoph_albums ADD COLUMN sortorder varchar(32) DEFAULT null;
ALTER TABLE zoph_categories ADD COLUMN sortorder varchar(32) DEFAULT null;
ALTER TABLE zoph_places ADD COLUMN url varchar(1024) DEFAULT null AFTER country;
ALTER TABLE zoph_places ADD COLUMN urldesc varchar(32) DEFAULT null AFTER url;

CREATE TABLE zoph_comments (
  comment_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  comment_date datetime default NULL,
  subject varchar(255) default NULL,
  comment blob,
  timestamp timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  ipaddr varchar(16) default '',
  PRIMARY KEY  (comment_id)
) TYPE=MyISAM;

CREATE TABLE zoph_photo_comments (
  photo_id int(11) NOT NULL default '0',
  comment_id int(11) NOT NULL default '0',
  PRIMARY KEY  (photo_id,comment_id)
) TYPE=MyISAM;

ALTER TABLE zoph_users ADD column leave_comments char(1) NOT NULL DEFAULT '0' after import;

CREATE TABLE zoph_photo_relations (
  photo_id_1 int(11) NOT NULL default '0',
  photo_id_2 int(11) NOT NULL default '0',
  desc_1 varchar(128) default NULL,
  desc_2 varchar(128) default NULL,
  PRIMARY KEY  (photo_id_1,photo_id_2)
) TYPE=MyISAM;


ALTER TABLE zoph_photos MODIFY rating float (4,2) unsigned;

