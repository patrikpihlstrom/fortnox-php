<?php
/**
 * Created by PhpStorm.
 * User: patrik
 * Date: 2018-06-01
 * Time: 15:26
 */

namespace patrikpihlstrom\Fortnox;


class Client
{
    /** @var string $_accessToken */
    protected $_accessToken;

    /** @var string $_clientSecret */
    protected $_clientSecret;

    /** @var string $_host */
    protected $_host;

    public function __construct($accessToken, $clientSecret, $host = 'https://api.fortnox.se/3/')
    {
        $this->_accessToken = $accessToken;
        $this->_clientSecret = $clientSecret;
        $this->_host = $host;
    }

    public function getAccounts()
    {
        return $this->call('GET', 'accounts');
    }

    public function createInvoice($issues, $customerNumber)
    {
        $body = ['Invoice' => ['InvoiceRows' => [], 'CustomerNumber' => $customerNumber]];
        foreach ($issues as $issue)
        {
            $row = ['DeliveredQuantity' => $this->_toHours($issue['to_bill']),
                    'ArticleNumber' => 103,
                    'Description' => $issue['key'] . ': ' . $issue['summary']];
            $body['Invoice']['InvoiceRows'][] = $row;
        }

        if (!empty($body['Invoice']['InvoiceRows']))
        {
            return $this->call('POST', 'invoices', json_encode($body));
        }

        return ['status' => '400', 'message' => 'No issues specified.'];
    }

    public function call($method, $entity, $body = null)
    {
        $curl = curl_init($this->_host . $entity);
        $options = [
            'Access-Token: ' . $this->_accessToken,
            'Client-Secret: ' . $this->_clientSecret,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $options);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'POST' || $method == 'PUT')
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $curlResponse = curl_exec($curl);
        curl_close($curl);
        return $curlResponse;
    }

    private function _toHours($time)
    {
        $time = explode(':', $time);
        return floatval($time[0] + $time[1] * (1 / 60) + $time[2] * (1 / 3600));
    }
}
