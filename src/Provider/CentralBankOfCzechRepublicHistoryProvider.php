<?php

/*
 * This file is part of Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swap\Provider;

use Swap\Exception\UnsupportedCurrencyPairException;
use Swap\Model\CurrencyPair;
use Swap\Model\Rate;
use Swap\Util\CurrencyCodes;
use Swap\HistoryProviderInterface;

/**
 * Central Bank of Czech Republic (CNB) history provider.
 *
 * @author Petr Kramar <petr.kramar@perlur.cz>
 */
class CentralBankOfCzechRepublicHistoryProvider extends AbstractProvider implements HistoryProviderInterface
{
    const URL_FORMAT = 'http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/rok.txt?rok=%d';
    const DATE_FORMAT = 'd.m.Y';

    /**
     * @var array stored rates [YYYY-MM-DD][CUR] => Rate
     */
    protected $rates;

    /**
     * @var \DateTime date to retrieve rates from
     */
    protected $dateTime;

    /**
     * {@inheritdoc}
     */
    public function setDate(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \UnexpectedValueException
     */
    public function fetchRate(CurrencyPair $currencyPair)
    {
        if (!($this->dateTime instanceof \DateTime)) {
            throw new \UnexpectedValueException('Date is not set.');
        }

        if (CurrencyCodes::ISO_CZK !== $currencyPair->getQuoteCurrency()) {
            throw new UnsupportedCurrencyPairException($currencyPair);
        }

        $dateTime = $this->dateTime->format('Y-m-d');

        if (!$this->rates) {
            $this->retrieveRates($this->fetchContent($this->buildUrl($this->dateTime->format('Y'))));
        }

        if (!isset($this->rates[$dateTime])) {
            throw new \InvalidStateException("Rate at {$dateTime} not available.");
        }

        if (!isset($this->rates[$dateTime][$currencyPair->getBaseCurrency()])) {
            throw new \InvalidStateException("Rate at {$dateTime} for '{$currency->getBaseCurrency()}' not found.");
        }

        return $this->rates[$dateTime][$currencyPair->getBaseCurrency()];
    }

    /**
     * Retrieve rates.
     *
     * @param string $content
     *
     * @throws \InvalidArgumentException
     */
    private function retrieveRates($content)
    {
        $rows = explode("\n", $content);
        $row_count = count($rows);
        if ($row_count < 2) {
            return;
        }

        $currencies = $this->parseHeader($rows[0]);
        for ($i = 1; $i < $row_count; ++$i) {
            if (empty($rows[$i])) {
                // skip empty lines
                continue;
            }

            $row_fields = explode('|', $rows[$i]);
            $date = \DateTime::createFromFormat(self::DATE_FORMAT, $row_fields[0]);
            $date->setTime(0, 0, 0);
            $date_key = $date->format('Y-m-d');
            foreach ($currencies as $currency => $c) {
                $index = $c->index;
                $base = (int) $c->base;
                $rate = (float) str_replace(',', '.', $row_fields[$index]);
                $this->rates[$date_key][$currency] = new Rate((string) ($rate / $base), $date);
            }
        }
    }

    /**
     * Parse header and returns hash with following properties:
     * [ 'CURRENCY' => (object) [ 'base' => int, 'index' => int ]]
     *   - base is usually 1 or 100 depending on currency
     *   - index specifies which field in $row belongs to
     *     particular currency
     *
     * @param string $row
     *
     * @return array
     */
    private function parseHeader($row)
    {
        $currencies = [];
        $split = explode('|', $row);

        // $i = 1 to skip first (date) field
        for ($i = 1; $i < count($split); ++$i) {
            list($base, $currency) = explode(' ', $split[$i]);
            $currencies[$currency] = (object) ['base' => (int) $base, 'index' => $i];
        }

        return $currencies;
    }


    /**
     * Build url.
     *
     * @param int $year year
     *
     * @return string
     */
    private function buildUrl($year)
    {
        return sprintf(self::URL_FORMAT, $year);
    }
}
