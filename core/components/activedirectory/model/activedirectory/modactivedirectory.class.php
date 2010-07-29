<?php
/**
 * The base class for ActiveDirectory.
 *
 * @package activedirectory
 */
class modActiveDirectory {
    
    function __construct(modX &$modx,array $config = array()) {
        $this->modx =& $modx;

        $corePath = $this->modx->getOption('activedirectory.core_path',$config,$this->modx->getOption('core_path').'components/activedirectory/');
        $assetsPath = $this->modx->getOption('activedirectory.assets_path',$config,$this->modx->getOption('assets_path').'components/activedirectory/');
        $assetsUrl = $this->modx->getOption('activedirectory.assets_url',$config,$this->modx->getOption('assets_url').'components/activedirectory/');
        $connectorUrl = $assetsUrl.'connector.php';

        $this->config = array_merge(array(
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl.'css/',
            'jsUrl' => $assetsUrl.'js/',
            'imagesUrl' => $assetsUrl.'images/',

            'connectorUrl' => $connectorUrl,

            'corePath' => $corePath,
            'modelPath' => $corePath.'model/',
            'chunksPath' => $corePath.'elements/chunks/',
            'pagesPath' => $corePath.'elements/pages/',
            'snippetsPath' => $corePath.'elements/snippets/',
            'processorsPath' => $corePath.'processors/',
            'hooksPath' => $corePath.'hooks/',
            'useCss' => true,
            'loadJQuery' => true,
        ),$config);

        $this->modx->addPackage('activedirectory',$this->config['modelPath']);
    }

    /**
     * Initializes ActiveDirectory into different contexts.
     *
     * @access public
     * @param string $ctx The context to load. Defaults to web.
     */
    public function initialize($ctx = 'web') {
        switch ($ctx) {
            case 'mgr':
                if (!$this->modx->loadClass('activedirectory.request.ADirControllerRequest',$this->config['modelPath'],true,true)) {
                    return 'Could not load controller request handler.';
                }
                $this->request = new ADirControllerRequest($this);
                return $this->request->handleRequest();
            break;
            case 'connector':

                if (!$this->modx->loadClass('activedirectory.request.ADirConnectorRequest',$this->config['modelPath'],true,true)) {
                    return 'Could not load connector request handler.';
                }
                $this->request = new ADirConnectorRequest($this);
                return $this->request->handle();
            break;
            default:
                $this->modx->lexicon->load('activedirectory:web');
            break;
        }
    }

    /**
     * Gets a Chunk and caches it; also falls back to file-based templates
     * for easier debugging.
     *
     * @access public
     * @param string $name The name of the Chunk
     * @param array $properties The properties for the Chunk
     * @return string The processed content of the Chunk
     */
    public function getChunk($name,array $properties = array()) {
        $chunk = null;
        if (!isset($this->chunks[$name])) {
            $chunk = $this->modx->getObject('modChunk',array('name' => $name),true);
            if (empty($chunk)) {
                $chunk = $this->_getTplChunk($name);
                if ($chunk == false) return false;
            }
            $this->chunks[$name] = $chunk->getContent();
        } else {
            $o = $this->chunks[$name];
            $chunk = $this->modx->newObject('modChunk');
            $chunk->setContent($o);
        }
        $chunk->setCacheable(false);
        return $chunk->process($properties);
    }
    /**
     * Returns a modChunk object from a template file.
     *
     * @access private
     * @param string $name The name of the Chunk. Will parse to name.chunk.tpl
     * @return modChunk/boolean Returns the modChunk object if found, otherwise
     * false.
     */
    private function _getTplChunk($name) {
        $chunk = false;
        $f = $this->config['chunksPath'].strtolower($name).'.chunk.tpl';
        if (file_exists($f)) {
            $o = file_get_contents($f);
            $chunk = $this->modx->newObject('modChunk');
            $chunk->set('name',$name);
            $chunk->setContent($o);
        }
        return $chunk;
    }
}