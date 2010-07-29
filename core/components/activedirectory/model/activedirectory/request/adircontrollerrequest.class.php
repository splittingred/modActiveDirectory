<?php
require_once MODX_CORE_PATH . 'model/modx/modrequest.class.php';
/**
 * Encapsulates the interaction of MODx manager with an HTTP request.
 *
 * {@inheritdoc}
 *
 * @package activedirectory
 * @extends modRequest
 */
class ADirControllerRequest extends modRequest {
    public $gallery = null;
    public $actionVar = 'action';
    public $defaultAction = 'home';

    function __construct(ActiveDirectory &$adir) {
        parent :: __construct($adir->modx);
        $this->adir =& $adir;
    }

    /**
     * Extends modRequest::handleRequest and loads the proper error handler and
     * actionVar value.
     *
     * {@inheritdoc}
     */
    public function handleRequest() {
        $this->loadErrorHandler();

        /* save page to manager object. allow custom actionVar choice for extending classes. */
        $this->action = isset($_REQUEST[$this->actionVar]) ? $_REQUEST[$this->actionVar] : $this->defaultAction;

        $modx =& $this->modx;
        $adir =& $this->adir;

        $modx->regClientCSS($adir->config['cssUrl'].'mgr.css');
        $viewHeader = include $this->adir->config['corePath'].'controllers/mgr/header.php';

        $f = $this->gallery->config['corePath'].'controllers/mgr/'.strtolower($this->action).'.php';
        if (file_exists($f)) {
            $this->modx->lexicon->load('activedirectory:default');
            $viewOutput = include $f;
        } else {
            $viewOutput = 'Action not found: '.$f;
        }

        return $viewHeader.$viewOutput;
    }
}