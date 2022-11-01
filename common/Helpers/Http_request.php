<?php

namespace common\Helpers;

use yii\base\Component;
use Yii;

// A pretty basic but effective class that can be used for doing CRUD operations on Microsoft Dynamics NAV's Odata services

error_reporting(E_ALL);
ini_set("display_errors", 1);

class http_request extends Component
{

    protected $_handle = NULL;
    protected $_credentials = '';
    protected $_url = '';
    protected $_service = '';
    protected $_company = '';
    protected $_suffix = '';
    protected $_requestType = '';
    protected $_header = [
        'Connection: Keep-Alive',
        'Accept: application/json',
        'Content-Type: application/json; charset=utf-8',
        'DataServiceVersion: 3.0',
        'MaxDataServiceVersion: 3.0',
        'Prefer: return-content',
        "Accept: */*"
    ];

    protected function _ifMatch($header, $etag)
    {
        array_push($header, 'If-Match: W/"\'' . $etag . '\'"');
        return $header;
    }

    protected function _setData($data)
    {
        if (!empty($data)) {
            if (count($data) === 1) {
                if (is_array($data[0])) {
                    $this->_suffix = $this->_buildSuffix(NULL, $data[0]);
                } else {
                    $this->_suffix = $this->_buildSuffix($data[0], NULL);
                }
            } else {
                $this->_suffix = $this->_buildSuffix($data[0], $data[1]);
            }
        } else {
            $this->_suffix = $this->_buildSuffix(NULL, NULL);
        }
    }

    protected function _buildCompanySuffix()
    {
        if ($this->_company === NULL) {
            return '';
        }
        return 'Company(\'' . $this->_company . '\')/';
    }

    protected function _buildIDSuffix($id = NULL)
    {
        if ($id === NULL) {
            return '';
        }
        return '(\'' . $id . '\')';
    }

    protected function _buildQuerySuffix($fields = NULL)
    {
        if ($fields === NULL) {
            return '';
        }
        if ($this->_requestType === 'GET') {
            $this->setOption('postfields', '');
            return '?' . urldecode(http_build_query(['$select' => implode(',', $fields)]));
        } elseif ($this->_requestType === 'DELETE') {
            $this->setOption('postfields', '');
            return '';
        } else {
            $this->setOption('postfields', json_encode($fields));
            return '';
        }
    }

    protected function _buildSuffix($id, $q)
    {
        return $this->_buildCompanySuffix() . $this->_service . $this->_buildIDSuffix($id) . $this->_buildQuerySuffix($q);
    }

    protected function _setRequestType($requestType, $data)
    {
        $this->_requestType = $requestType;
        $this->_suffix = '';
        $this->_setData($data);
        switch ($requestType) {
            case 'GET':
            case 'POST':
                $this->setOptions([
                    'customrequest' => $requestType,
                    'post' => ($requestType === 'POST'),
                    'url' => $this->_url . $this->_suffix,
                    'httpheader' => $this->_header
                ]);
                break;
            case 'PATCH':
            case 'DELETE':
                $this->setOptions([
                    'customrequest' => $requestType,
                    'post' => true,
                    'url' => $this->_url . $this->_suffix,
                    'httpheader' => $this->_ifMatch($this->_header, $data[1]["ETag"])
                ]);
                break;
            default:
                // DO NOTHING YET
        }
    }

    protected function _initService($provider = [], $options = [])
    {
        if (!is_array($provider)) {
            $provider = json_decode(file_get_contents($provider), true);
        }
        if (isset($provider['url']) && $provider['url'] !== '') {
            $this->setProvider($provider['url']);
        }
        if (isset($provider['credentials']) && $provider['credentials'] !== '') {
            $this->setCredentials($provider['credentials']);
        }
        if (isset($options['service']) && $options['service'] !== '') {
            $this->setService($options['service']);
        }
        if (isset($options['company']) && $options['company'] !== '') {
            $this->setCompany($options['company']);
        }
        $this->_handle = curl_init();
        $this->setOptions([
            'returntransfer' => true,
            'userpwd' => $this->_credentials
        ]);
    }

    protected function _send()
    {
        return json_decode(curl_exec($this->_handle), TRUE);
    }

    public static function factory($provider = [], $options = [])
    {
        return new static($provider, $options);
    }

    public function __construct($provider = [], $options = [])
    {
        $this->_initService($provider, $options);
    }

    public function create($data)
    {
        $this->_setRequestType('POST', [$data]);
        return $this->_send();
    }

    public function read()
    {
        $this->_setRequestType('GET', func_get_args());
        return $this->_send();
    }

    public function update($id, $data)
    {
        $olddata = $this->read($id, ['No', 'ETag']);
        $data['No'] = $olddata['No'];
        $data['ETag'] = $olddata['ETag'];
        $this->_setRequestType('PATCH', [$id, $data]);
        return $this->_send();
    }

    public function delete($id)
    {
        $olddata = $this->read($id, ['No', 'ETag']);
        $this->_setRequestType('DELETE', [$id, $olddata]);
        return $this->_send();
    }

    public function setProvider($url)
    {
        $this->_url = $url;
    }

    public function setCredentials($credentials = '')
    {
        $this->_credentials = $credentials;
    }

    public function setService($service = '')
    {
        $this->_service = $service;
    }

    public function setCompany($company = '')
    {
        $this->_company = $company;
    }

    public function setOption($key, $value)
    {
        curl_setopt($this->_handle, constant("CURLOPT_" . strtoupper($key)), $value);
        return $this;
    }

    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
        return $this;
    }

    public function __destruct()
    {
        // Close handle
        curl_close($this->_handle);
    }


    public static function client($svc)
    {
        $server = Yii::$app->params['SERVER'];
        $port = Yii::$app->params['PORT'];
        $instance = Yii::$app->params['INSTANCE'];
        $password = Yii::$app->params['PWD'];
        $username = Yii::$app->params['USERNAME'];
        $company = Yii::$app->params['COMPANY'];

        $service = self::factory([
            "url" => "http://francis:6048/DynamicsNAV90/OData/",
            "credentials" => "{$username}:{$password}"
        ], [
            'company' => $company,
            'service' => Yii::$app->params['SERVICES'][$svc]
        ]);

        return $service;
    }
}
