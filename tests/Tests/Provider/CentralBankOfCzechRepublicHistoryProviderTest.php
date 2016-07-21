<?php

/*
 * This file is part of Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swap\Tests\Provider;

use Swap\Model\CurrencyPair;
use Swap\Provider\CentralBankOfCzechRepublicHistoryProvider;

/**
 * @author Petr Kramar <petr.kramar@perlur.cz>
 */
class CentralBankOfCzechRepublicHistoryProviderTest extends AbstractProviderTestCase
{
    /**
     * @var array urls of CNB exchange rates in history
     */
    protected static $urls;

    /**
     * @var array contents of CNB exchange rates in history
     */
    protected static $contents;

    /**
     * Set up variables before TestCase is being initialized.
     */
    public static function setUpBeforeClass()
    {
        self::$urls = [];
        self::$contents = [];

        foreach ([2014,2015] as $year) {
            $url = 'http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/rok.txt?rok='.$year;
            self::$urls[] = $url;
            self::$contents[] = file_get_contents(__DIR__.'/../../Fixtures/Provider/CentralBankOfCzechRepublic/cnb_year_'.$year.'.txt');
        }
    }

    /**
     * Clean variables after TestCase finish.
     */
    public static function tearDownAfterClass()
    {
        self::$urls = [];
        self::$contents = [];
    }

    /**
     * @test
     * @expectedException \Swap\Exception\UnsupportedCurrencyPairException
     */
    public function itThrowsAnExceptionWhenQuotesIsNotCzk()
    {
        $provider = $this->createProvider();
        $provider->fetchRate(new CurrencyPair('CZK', 'EUR'));
    }

    /**
     * @test
     * @expectedException \Swap\Exception\UnsupportedCurrencyPairException
     */
    public function itThrowsAnExceptionWhenThePairIsNotSupported()
    {
        $provider = $this->createProvider();
        $provider->fetchRate(new CurrencyPair('XXX', 'TRY'));
    }

    /**
     * @test
     * @dataProvider ratesProvider
     */
    public function itFetchesRates(CurrencyPair $pair, \DateTime $rateDate, $rateValue)
    {
        $provider = $this->createProvider();
        $provider->setDate($rateDate);
        $rate = $provider->fetchRate($pair);

        $this->assertSame($rateValue, $rate->getValue());
        $this->assertEquals($rateDate, $rate->getDate());
    }

    public function ratesProvider()
    {
        return [
            [new CurrencyPair('EUR', 'CZK'), new \DateTime('2015-01-30'), '27.795'],
            [new CurrencyPair('AUD', 'CZK'), new \DateTime('2015-01-02'), '18.665'],
            [new CurrencyPair('GBP', 'CZK'), new \DateTime('2015-01-20'), '36.457'],
            [new CurrencyPair('USD', 'CZK'), new \DateTime('2014-12-23'), '22.645'],
        ];
    }

    /**
     * Create bank provider.
     *
     * @return CentralBankOfCzechRepublicProvider
     */
    protected function createProvider()
    {
        $adapter = $this->getHttpAdapterMockWithMultipleUrls(self::$urls, self::$contents);
        $provider = new CentralBankOfCzechRepublicHistoryProvider($adapter);
        $provider->setDate(new \DateTime('2014-01-01'));

        return $provider;
    }
}
