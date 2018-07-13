<?php

class ContactmanagerTest extends PHPUnit_Framework_TestCase {

	public static function setUpBeforeClass() {
		include __DIR__."/../Contactmanager.class.php";
	}

	public function setUp() {
		//mock freepbx
		$freepbx = \Mockery::mock('\FreePBX');

		//mock userman
		$freepbx->Userman = \Mockery::mock('\FreePBX\Modules\Userman');

		//mock database
		$this->database = $freepbx->Database = \Mockery::mock('\FreePBX\Database');
		//mock PDO Statement
		$this->PDOStatement = \Mockery::mock('\FreePBX\Database\Stmt');

		//mock config
		$freepbx->Config = $this->getMockBuilder('\FreePBX\Config')
		->setMethods(array('get'))
		->disableAutoload()
		->disableOriginalConstructor()
		->getMock();

		$freepbx->Config->method('get')->with('ASTSPOOLDIR')->will($this->returnValue(''));

		$this->contactmanager = new \FreePBX\modules\Contactmanager($freepbx);

		$this->faker = Faker\Factory::create();
	}

	public function testInitalization() {
		$this->assertEquals($this->contactmanager->tmp, '/tmp');
	}

	public function testgetContactImageEmpty() {
		//Test no information sent that we get back nothing
		$buffer = $this->contactmanager->getContactImage();
		$this->assertEmpty($buffer);
	}

	public function testgetContactImageTemporary() {
		//test with temporary set
		$_REQUEST['temporary'] = 1;
		$_REQUEST['name'] = 'jim.jpg';
		$data = $this->faker->text();
		file_put_contents('/tmp/jim.jpg',$data);
		$buffer = $this->contactmanager->getContactImage();
		$this->assertEquals($data, $buffer);
		@unlink('/tmp/jim.jpg');
	}
}
