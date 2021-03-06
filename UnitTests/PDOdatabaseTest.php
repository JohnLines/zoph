<?php
/**
 * Test the database classes
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
 * @package ZophUnitTest
 * @author Jeroen Roos
 */

require_once "testSetup.php";

use db\select;
use db\db;
use db\param;
use db\insert;
use db\delete;
use db\clause;

/**
 * Test class that tests the database classes
 * @package ZophUnitTest
 * @author Jeroen Roos
 */
class PDOdatabaseTest extends ZophDataBaseTestCase {
    /**
     * Create SELECT queries
     * @dataProvider getQueries();
     * @param string Table to run query on
     * @param array Fields to query
     * @param string Expected SQL query
     */
    public function testCreateQuery($table, $fields, $exp_sql) {
        $qry=new select($table);
        if (is_array($fields)) {
            $qry->addFields($fields);
        }
        $this->assertEquals($exp_sql, (string) $qry);

        $result=db::query($qry);
        $this->assertInstanceOf("PDOStatement", $result);
    }

    /**
     * Test a SELECT query with a WHERE clause
     */
    public function testQueryWithClause() {

        $qry=new select("photos");
        $where=new clause("photo_id > :minid");
        $qry->addParam(new param(":minid", 5, PDO::PARAM_INT));

        $qry->where($where);

        $sql=(string) $qry;
        $exp_sql="SELECT * FROM zoph_photos WHERE (photo_id > :minid);";

        $this->assertEquals($exp_sql, $sql);

        unset($qry);
        unset($clause);

        $qry=new select("photos");
        $where=new clause("photo_id > :minid");
        $where->addAnd(new clause("photo_id < :maxid"));
        $qry->addParams(array(
            new param(":maxid", 10, PDO::PARAM_INT),
            new param(":minid", 5, PDO::PARAM_INT)
        ));

        $qry->where($where);

        $sql=(string) $qry;
        $exp_sql="SELECT * FROM zoph_photos WHERE (photo_id > :minid) AND ((photo_id < :maxid));";

        $this->assertEquals($exp_sql, $sql);

        unset($qry);
        unset($clause);

    }

    /**
     * Test a SELECT query with a GROUP BY and HAVING clause
     */
    public function testQueryWithGroupBy() {

        $qry=new select("photos");
        $qry->addGroupBy("album");
        $having=new clause("album_id > :albid");
        $qry->addParam(new param(":albid", 5, PDO::PARAM_INT));

        $qry->having($having);

        $sql=(string) $qry;
        $exp_sql="SELECT * FROM zoph_photos GROUP BY album HAVING (album_id > :albid);";

        $this->assertEquals($exp_sql, $sql);

        unset($qry);
        unset($clause);

        $qry=new select("photos");
        $qry->addGroupBy("album");
        $where=new clause("photo_id > :photoid");
        $having=new clause("album_id > :albid");
        $qry->addParams(array(
            new param(":photoid", 10, PDO::PARAM_INT),
            new param(":albid", 5, PDO::PARAM_INT)
        ));

        $qry->where($where);
        $qry->having($having);

        $sql=(string) $qry;
        $exp_sql="SELECT * FROM zoph_photos WHERE (photo_id > :photoid) GROUP BY album HAVING (album_id > :albid);";

        $this->assertEquals($exp_sql, $sql);

        unset($qry);
        unset($clause);

    }

    /**
     * Test a SELECT query with a JOIN clause
     */
    public function testQueryWithJoin() {
        $qry=new select(array("p" => "photos"));
        $qry->addFields(array("name"));
        $where=new clause("p.photo_id = :photoid");
        $qry->addParam(new param(":photoid", 5, PDO::PARAM_INT));
        $qry->join(array("pa" => "photo_albums"),"p.photo_id=pa.photo_id")
            ->join(array("a" => "albums"),"pa.album_id=a.album_id")
            ->where($where);
        $sql=(string) $qry;
        $exp_sql="SELECT p.name FROM zoph_photos AS p " .
                 "INNER JOIN zoph_photo_albums AS pa " .
                 "ON p.photo_id=pa.photo_id " .
                 "INNER JOIN zoph_albums AS a " .
                 "ON pa.album_id=a.album_id " .
                 "WHERE (p.photo_id = :photoid);";

        $this->assertEquals($exp_sql, $sql);
    }

    /**
     * Test a SELECT query with a subquery in the FROM clause
     */
    public function testQueryWithFROMSubquery() {
        $subqry=new select(array("pr" => "photo_ratings"));
        $subqry->addFields(array("photo_id"));
        $subqry->addFunction(array("rating" => "FLOOR(avg(pr.rating)+0.5)"));
        $subqry->where(new clause("photo_id < :photoid"));
        $param=new param(":photoid", 25, PDO::PARAM_INT);
        $subqry->addParam($param);

        $qry=new select(array("avg_rating" => $subqry));
        $qry->addFields(array("rating"));
        $qry->addFunction(array("count" => "COUNT(*)"));
        $qry->addGroupBy("rating");

        $sql=(string) $qry;
        $exp_sql="SELECT rating, COUNT(*) AS count FROM " .
                 "(SELECT pr.photo_id, FLOOR(avg(pr.rating)+0.5) AS rating " .
                 "FROM zoph_photo_ratings AS pr " .
                 "WHERE (photo_id < :photoid)) AS avg_rating " .
                 "GROUP BY rating;";

        $this->assertEquals($exp_sql, $sql);

        $this->assertEquals(array($param), $qry->getParams());

    }

    /**
     * Test a query with LIMIT
     * @dataProvider getLimits();
     */
    public function testQueryWithLimit($count, $offset) {
        $qry=new select("photos");
        $qry->addLimit($count, $offset);
        $sql=(string) $qry;
        $exp_sql="SELECT * FROM zoph_photos";
        if (!is_null($offset)) {
            if (is_null($count)) {
                $exp_sql .= " LIMIT " . (int) $offset . ", 999999999999";
            } else {
                $exp_sql .= " LIMIT " . (int) $offset . ", " . (int) $count;
            }
        } else {
            if (!is_null($count)) {
                $exp_sql .= " LIMIT " . (int) $count;
            }
        }
        $exp_sql .= ";";
        $this->assertEquals($exp_sql, $sql);
    }

    /**
     * Test a query with ORDER BY
     * @dataProvider getOrders();
     */
    public function testQueryWithOrder(array $orders) {
        $qry=new select("photos");
        foreach ($orders as $order) {
            $qry->addOrder($order);
        }
        $sql=(string) $qry;
        $exp_sql="SELECT * FROM zoph_photos ORDER BY " . implode(", ", $orders);
        $exp_sql .= ";";
        $this->assertEquals($exp_sql, $sql);
    }

    public function testHasTable() {
        $qry=new select(array("p" => "photos"));

        $this->assertTrue($qry->hasTable("photos"));
        $this->assertFalse($qry->hasTable("albums"));

        $qry->join(array("pa" => "photo_albums"), "p.photo_id=pa_.photo_id");

        $this->assertTrue($qry->hasTable("photos"));
        $this->assertTrue($qry->hasTable("photo_albums"));
        $this->assertFalse($qry->hasTable("albums"));

        $qry->join(array("a" => "albums"), "pa.album_id=a.album_id");

        $this->assertTrue($qry->hasTable("photos"));
        $this->assertTrue($qry->hasTable("photo_albums"));

        $qry=new select(array("p" => "photos"));

        $this->assertTrue($qry->hasTable("photos"));
        $this->assertFalse($qry->hasTable("albums"));

        $qry->join(array("pa" => "photo_albums"), "p.photo_id=pa.photo_id");

        $this->assertTrue($qry->hasTable("photos"));
        $this->assertTrue($qry->hasTable("photo_albums"));
        $this->assertFalse($qry->hasTable("albums"));
    }

    /**
     * test INSERT query
     * @dataProvider getInserts();
     */
    public function testInsert($table, array $values) {
        $qry=new insert(array($table));
        foreach ($values as $field => $value) {
            $qry->addParam(new param(":" . $field, $value, PDO::PARAM_STR));
        }

        $qry->execute();

        $qry=new select(array($table));
        $qry->addFields(array_keys($values));

        $where=null;
        foreach ($values as $field => $value) {
            $clause=new clause($field . "=:" . $field);
            if ($where instanceof clause) {
                $where->addAnd($clause);
            } else {
                $where=$clause;
            }

            $qry->addParam(new param(":" . $field, $value, PDO::PARAM_STR));
        }
        $qry->where($where);
        $result=$qry->execute()->fetchAll();

        $this->assertEquals(1, count($result));
    }

    /**
     * test DELETE query
     */
    public function testDelete() {
        $db=db::getHandle();

        $ids=array();

        // First insert a few rows in photo table;
        for ($i=0; $i<3; $i++) {
            $qry=new insert(array("photos"));
            $qry->addParam(new param(":name", "test123", PDO::PARAM_STR));
            $qry->execute();
            $ids[]=$db->lastInsertId();
        }

        // Check if this succeeded
        $this->assertEquals(3, sizeof($ids));

        // Now delete them
        foreach ($ids as $id) {
            $qry=new delete(array("photos"));
            $qry->where(new clause("photo_id=:photoid"));
            $qry->addParam(new param(":photoid", $id, PDO::PARAM_INT));
            $qry->execute();
        }

        // And check if they're gone
        foreach ($ids as $id) {
            $qry=new select(array("photos"));
            $qry->where(new clause("photo_id=:photoid"));
            $qry->addParam(new param(":photoid", $id, PDO::PARAM_INT));
            $result=$qry->execute()->fetchAll();

            $this->assertEquals(0, count($result));
        }
    }

    public function testUnionQuery() {
        // Yes, this is a pointless query
        $qry=new select(array("p" => "photos"));
        $qry->where(new clause("photo_id=3"));
        $qry2=new select(array("p" => "photos"));
        $qry2->where(new clause("photo_id=2"));
        $qry->union($qry2);

        $sql=(string) $qry;
        $exp_sql="SELECT * FROM zoph_photos AS p WHERE (photo_id=3) " .
                 "UNION " .
                 "(SELECT * FROM zoph_photos AS p WHERE (photo_id=2));";
        $this->assertEquals($exp_sql, $sql);
   }


    /**
     * Provide queries to use as test input
     */
    public function getQueries() {
        return array(
            array("photos", array("photo_id"), "SELECT zoph_photos.photo_id FROM zoph_photos;"),
            array("photos", null, "SELECT * FROM zoph_photos;"),
            array("photos", array("photo_id", "name"),
                "SELECT zoph_photos.photo_id, zoph_photos.name FROM zoph_photos;")
        );
    }

    /**
     * Provide limits to test LIMIT queries
     */
    public function getLimits() {
        return array(
            array(null, null), // No no, no no, no no, there's no limit
            array(5, null),
            array(5, 1),
            array(null, 5)
        );
    }

    /**
     * Provide ORDER clauses
     */
    public function getOrders() {
        return array(
            array(array("name")),
            array(array("name DESC")),
            array(array("name", "photo_id")),
            array(array("name" , "photo_id DESC"))
        );
    }

    /**
     * Provide data for INSERT queries
     */
    public function getInserts() {
        return array(
            array("photos", array(
                "name" => "testname 1",
                "title" => "testtitle 1")),
            array("photos", array(
                "name" => "testname 2",
                "title" => "testtitle 2")),
            array("photos", array(
                "name" => "testname 3",
                "title" => "testtitle 3")),
            array("photo_categories", array(
                "photo_id" => 999,
                "category_id" => 1)),
            array("photo_categories", array(
                "photo_id" => 998,
                "category_id" => 2)),
            array("photo_categories", array(
                "photo_id" => 997,
                "category_id" => 3)),
            );
    }

}
