<?php
/**
 * Database query class for INSERT queries
 *
 * This file is part of Zoph.
 *
 * Zoph is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Zoph is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with Zoph; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package Zoph
 * @author Jeroen Roos
 */

/**
 * The insert object is used to create INSERT queries
 *
 * @package Zoph
 * @author Jeroen Roos
 */
class insert extends query {

    /**
     * Create INSERT query
     * @return string SQL query
     */
    public function __toString() {
        $sql = "INSERT INTO " . $this->table;
        
        $fields=array();

        foreach($this->getParams() as $param) {
            $fields[]=substr($param->getName(),1);
        }

        $sql.=" (" . implode(", ", $fields) . ")";
        $sql.=" VALUES(:" . implode(", :", $fields) . ") ";

        return $sql . ";";
    }

}
