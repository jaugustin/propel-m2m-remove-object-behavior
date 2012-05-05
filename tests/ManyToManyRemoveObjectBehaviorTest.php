<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * @author     Jérémie Augustin <jeremie.augustin@pixel-cookers.com>
 */
class ManyToManyRemoveObjectBehaviorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('Book')) {
            $schema = <<<EOF
<database name="bookstore" defaultIdMethod="native">
    <behavior name="many_to_many_remove_object" />
    <table name="book" description="Book Table">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Book Id" />
        <column name="title" type="VARCHAR" required="true" description="Book Title" primaryString="true" />
    </table>

    <table name="author" description="Author Table">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Author Id" />
        <column name="first_name" required="true" type="VARCHAR" size="128" description="First Name" />
        <column name="last_name" required="true" type="VARCHAR" size="128" description="Last Name" />
    </table>

    <table name="book_author" isCrossRef="true">
        <column name="book_id" required="true" primaryKey="true" type="INTEGER" description="Author Id" />
        <column name="author_id" required="true" primaryKey="true" type="INTEGER" description="Author Id" />
        <foreign-key foreignTable="book" onDelete="cascade">
            <reference local="book_id" foreign="id" />
        </foreign-key>
        <foreign-key foreignTable="author" onDelete="cascade">
            <reference local="author_id" foreign="id" />
        </foreign-key>
    </table>
</database>
EOF;
            $builder = new PropelQuickBuilder();
            $config = $builder->getConfig();
            $config->setBuildProperty('behavior.many_to_many_remove_object.class', 'ManyToManyRemoveObjectBehavior');
            $builder->setConfig($config);
            $builder->setSchema($schema);
            $con = $builder->build();
        }
    }

    public function testRemoveMethodExist()
    {
        $book = new Book();
        $this->assertTrue(method_exists($book, 'addAuthor'), 'addAuthor exists');
        $this->assertTrue(method_exists($book, 'removeAuthor'), 'removeAuthor exists');
    }

    public function testRemoveObject()
    {
        $book = new Book();
        $this->assertCount(0, $book->getAuthors(), 'No Authors');

        $author1 = new Author();
        $author1->setFirstName('Bob');
        $author1->setLastName('Sponge');

        $book->addAuthor($author1);

        $author2 = new Author();
        $author2->setFirstName('Ella');
        $author2->setLastName('Maire');

        $book->addAuthor($author2);
        $this->assertCount(2, $book->getAuthors(), 'Two Authors');

        $book->removeAuthor($author1);
        $this->assertCount(1, $book->getAuthors(), 'One Author has been remove');
    }

    public function testRemoveObjectFromDB()
    {
        Propel::disableInstancePooling();
        BookQuery::create()->deleteAll();
        AuthorQuery::create()->deleteAll();
        $book = new Book();
        $book->setTitle('MyBook');
        $this->assertCount(0, $book->getAuthors(), 'No Authors');

        $author1 = new Author();
        $author1->setFirstName('Bob');
        $author1->setLastName('Sponge');
        $book->addAuthor($author1);

        $author2 = new Author();
        $author2->setFirstName('Ella');
        $author2->setLastName('Maire');
        $book->addAuthor($author2);
        $book->save();

        $this->assertEquals(2, BookAuthorQuery::create()->count(), 'Two Authors');
        $this->assertEquals(2, AuthorQuery::create()->count(), 'Two Authors');

        $book->removeAuthor($author1);
        $this->assertEquals(2, BookAuthorQuery::create()->count(), 'still Two Authors in db before save()');
        $this->assertCount(1, $book->getAuthors(), 'One Author has been remove');
        $book->save();

        $book = BookQuery::create()->findOne();
        $book->clearAuthors();

        $this->assertCount(1, $book->getAuthors(), 'One Author has been remove');
        $this->assertEquals(1, BookAuthorQuery::create()->count(), 'One Author has been remove');
    }
}
