<?php
/**
 * PingServiceBehavior
 *
 * Update ping servers automatically when content is updated
 *
 * PHP version 5
 *
 * @category    Behavior
 * @package     Croogo
 * @subpackage  Ceoogo.PingService
 * @version     1.0
 * @author      Md. Rayhan Chowdhury <ray@raynux.com>
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link        http://www.raynux.com
 */
class PingServiceBehavior extends ModelBehavior {
    
    // ping services
    private $__services = array(    
                                    'rest' => array(
                                                'http://blogsearch.google.com/ping',
                                                'http://rpc.weblogs.com/pingSiteForm'
                                        ),
        
                                    'rpc' => array(
                                                'http://rpc.pingomatic.com',
                                                'http://rpc.weblogs.com/RPC2',
                                                'http://blogsearch.google.com/ping/RPC2',
                                                'http://ping.feedburner.com',
                                            )
                                );

    /**
     * afterSave Hook
     * 
     * @param object $model
     * @param integer $created
     */
    function  afterSave(&$model, $created) {
        parent::afterSave($model, $created);
        
        if (!empty($model->data['Node']['status'])) {
            $options = array(
                                'name' => Configure::read('Site.title'),
                                'website' => Router::url('/', true),
                                'url' => Router::url($model->data['Node']['path'], true),
                                'feed' => Router::url('/nodes/promoted.rss', true)
                            );
            $this->ping($options);
        }
    }

    /**
     * Ping Blog Update Services
     *
     * @param array $options are name, website, url
     */
    function ping($options = array()) {
        
        $type = 'REST';
        if (function_exists('xmlrpc_encode_request')) {
            $type = 'XML-RPC';
        }

        switch ($type) {
            case 'REST':
                    // construct parameters
                    $params = array('name' => $options['name'], 'url' => $options['url']);
                    $params = array_map('rawurlencode', $params);
                    $paramString = http_build_query($params);

                    // Rest Update Ping Services
                    foreach ($this->__services['rest'] as $serviceApi) {
                        $requestUrl = $serviceApi . '?' . $paramString;
                        $response = file_get_contents($requestUrl);
                        
                    }

                break;

             case 'XML-RPC':
                    // construct parameters
                    $params = array($options['name'], $options['website'], $options['url'], $options['feed']);
                    $request = xmlrpc_encode_request("weblogUpdates.extendedPing", $params);

                    $context = stream_context_create(array('http' => array(
                        'method' => "POST",
                        'header' => "Content-Type: text/xml",
                        'content' => $request
                    )));

                    foreach ($this->__services['rpc'] as $endPoint) {
                        // Ping the services
                        $file = file_get_contents($endPoint, false, $context);
                        $response = xmlrpc_decode($file); //no need to process the response
                    }

                 break;
        }
    }

}
?>
