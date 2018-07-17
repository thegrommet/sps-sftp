<?php
declare(strict_types=1);

namespace Tests\Document;

use phpseclib\Net\SFTP;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use SpsConnector\Document\PurchaseOrder;
use SpsConnector\Sftp\Client;

/**
 * Purchase Order Doc Test Suite
 */
class PurchaseOrderTest extends TestCase
{
    public function testGetEdiType()
    {
        $document = new PurchaseOrder();
        $this->assertEquals(850, $document->getEdiType());
    }

    public function testFetchNewDocuments()
    {
        $document = $this->document();
        $sftp = $document->getSftpClient();
        $mockClient = $sftp->getClient();

        $xml = '<?xml version="1.0" encoding="utf-8"?><Orders xmlns="http://www.spscommerce.com/RSX"/>';

        $mockClient
            ->expects($this->exactly(3))
            ->method('delete')
            ->willReturn(true);
        $mockClient
            ->method('chdir')
            ->willReturn(true);
        $mockClient
            ->method('nlist')
            ->willReturn(['.', '..', 'PR12345', 'PR123456.xml', 'NOTAPO.xml', 'PR1234567.xml', 'pr4321']);
        $mockClient
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturn($xml);

        $documents = $document->fetchNewDocuments();
        $this->assertEquals(
            ['PR12345', 'PR123456.xml', 'PR1234567.xml'],
            array_keys($documents)
        );
        $this->assertInstanceOf(PurchaseOrder::class, $documents['PR12345']);
    }

    public function testGetXml()
    {
        $document = new PurchaseOrder();
        $xml = '<?xml version="1.0" encoding="utf-8"?><Orders xmlns="http://www.spscommerce.com/RSX"><Header/></Orders>';
        $document->setXml($xml);
        $this->assertInstanceOf(SimpleXMLElement::class, $document->getXml());
        $this->assertEquals(
            '<?xml version="1.0" encoding="utf-8"?><Orders ns="http://www.spscommerce.com/RSX"><Header/></Orders>',
            str_replace("\n", '', $document->getXml()->asXML())
        );
    }

    public function testGetXmlNotSet()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('XML has not been set.');
        $document = new PurchaseOrder();
        $document->getXml();
    }

    public function testGetXmlData()
    {
        $document = new PurchaseOrder();
        $document->setXml(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'PurchaseOrderTest.xml'));
        $this->assertEquals('525', $document->getXmlData('//Order/Header/OrderHeader/TradingPartnerId'));
        $this->assertEquals('', $document->getXmlData('//Order/Header/OrderHeader'));
        $this->assertEquals('1', $document->getXmlData('//Order/LineItem/OrderLine/LineSequenceNumber'));
    }

    public function testGetXmlChildren()
    {
        $document = new PurchaseOrder();
        $document->setXml(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'PurchaseOrderTest.xml'));
        $this->assertEquals(
            [new SimpleXMLElement('<data>525</data>')],
            $document->getXmlElements('//Order/Header/OrderHeader/TradingPartnerId')
        );
        $header = $document->getXmlElements('//Order/Header/OrderHeader');
        $this->assertCount(11, $header[0]->children());
    }

    private function document(): PurchaseOrder
    {
        $mockSftp = $this->getMockBuilder(SFTP::class)
            ->setMethods(['login', 'get', 'chdir', 'delete', 'nlist'])
            ->setConstructorArgs(['test.com'])
            ->getMock();

        $mockSftp
            ->expects($this->exactly(1))
            ->method('login')
            ->willReturn(true);

        $client = new Client('test.com', 'a', 'b');
        $client->setClient($mockSftp);

        $document = new PurchaseOrder($client);
        return $document;
    }
}