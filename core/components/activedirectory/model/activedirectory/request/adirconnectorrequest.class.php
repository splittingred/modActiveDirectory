<?php
require_once MODX_CORE_PATH . 'model/modx/modconnectorresponse.class.php';
/**
 * @package activedirectory
 */
class ADirConnectorRequest extends modConnectorResponse {

    function __construct(ActiveDirectory &$adir,array $config = array()) {
        $this->adir =& $adir;
        parent::__construct($adir->modx,$config);
    }

    public function handle($action = '') {
        if (empty($action) && !empty($_REQUEST['action'])) $action = $_REQUEST['action'];
        if (!isset($this->modx->error)) $this->loadErrorHandler();

        $path = $this->adir->config['processorsPath'].strtolower($action).'.php';
        $processorOutput = false;
        if (file_exists($path)) {
            $this->modx->lexicon->load('activedirectory:default');
            $modx =& $this->modx;
            $adir =& $this->adir;

            $scriptProperties = $_REQUEST;

            $processorOutput = include $path;
        } else {
            $processorOutput = $this->modx->error->failure('No action specified.');
        }
        if (is_array($processorOutput)) {
            $processorOutput = $this->modx->toJSON(array(
                'success' => isset($processorOutput['success']) ? $processorOutput['success'] : 0,
                'message' => isset($processorOutput['message']) ? $processorOutput['message'] : $this->modx->lexicon('error'),
                'total' => (isset($processorOutput['total']) && $processorOutput['total'] > 0)
                        ? intval($processorOutput['total'])
                        : (isset($processorOutput['errors'])
                                ? count($processorOutput['errors'])
                                : 1),
                'data' => isset($processorOutput['errors']) ? $processorOutput['errors'] : array(),
                'object' => isset($processorOutput['object']) ? $processorOutput['object'] : array(),
            ));
        }

        if (!isset($_FILES) && empty($_FILES)) {
            header("Content-Type: text/json; charset=UTF-8");
        }
        return $processorOutput;
    }
}