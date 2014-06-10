<?php

class DocFormatterTest extends PHPUnit_Framework_TestCase {

    private $formatter;

    public function setUp() {
        $this->formatter = new \mendeley\DocFormatter();
    }

    public function testOnlyTitle() {
        $result = $this->format([
                'authors' => [],
                'title' => 'Article title']);
        $this->assertEquals('Article title', $result);
    }

    public function testAuthorsAndTitle() {
        $result = $this->format([
                'authors' => [
                    ['forename' => 'John', 'surname' => 'Smith']
                ],
                'title' => 'The Article']);
        $this->assertEquals('Smith J. The Article', $result);
    }

    public function testAuthorWithFirstNameAlreadyAbbreviated() {
        $result = $this->format([
                'authors' => [
                    ['forename' => 'J.', 'surname' => 'Smith']
                ],
                'title' => 'The Article']);
        $this->assertEquals('Smith J. The Article', $result);
    }

    public function testAuthorWithFirstNameAndSecondName() {
        $result = $this->format([
                'authors' => [
                    ['forename' => 'John William', 'surname' => 'Smith']
                ],
                'title' => 'The Article']);
        $this->assertEquals('Smith J. W. The Article', $result);
    }

    public function testCyrillicSymbols() {
        mb_internal_encoding('UTF-8');
        $result = $this->format([
                'authors' => [
                    ['forename' => 'Иван', 'surname' => 'Иванов']
                ],
                'title' => 'Статья']);
        $this->assertEquals('Иванов И. Статья', $result);
    }

    private function format(array $array) {
        $obj = json_decode(json_encode($array));
        return $this->formatter->format($obj);
    }
}
