<?php
/**
 * TailwindHelper Test Case
 *
 * @package tailwindhelper
 * @subpackage test
 */

class TailwindHelperTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var modX $modx
     */
    protected $modx = null;
    /**
     * @var TailwindHelper $tailwindhelper
     */
    protected $tailwindhelper = null;
    /**
     * @var array $config The changed system settings, that are restored in tearDown
     */
    private $config = [];

    /**
     * Ensure all tests have a reference to the MODX and TailwindHelper objects
     */
    public function setUp(): void
    {
        $this->modx = TailwindHelperTestHarness::_getConnection();

        $corePath = $this->modx->getOption('tailwindhelper.core_path', null, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/tailwindhelper/');
        require_once $corePath . 'model/tailwindhelper/tailwindhelper.class.php';

        $this->tailwindhelper = new TailwindHelper($this->modx, [
            'core_path' => $corePath
        ]);
        // The processor gets this instance with getService. The services are an array in MODX 2 and a container in MODX 3
        $this->modx->tailwindhelper = $this->tailwindhelper;
        $this->modx->services['tailwindhelper'] = $this->tailwindhelper;

        error_reporting(E_ALL);
    }

    /**
     * Remove reference at end of test case
     */
    public function tearDown(): void
    {
        foreach ($this->config as $key => $value) {
            if ($value === null) {
                unset($this->modx->config[$key]);
            } else {
                $this->modx->config[$key] = $value;
            }
        }
        $this->config = [];
        unset($this->modx->services['tailwindhelper'], $this->modx->tailwindhelper);

        $this->modx = null;
        $this->tailwindhelper = null;
    }

    /**
     * Change a system setting for the current test only.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function setConfig(string $key, $value)
    {
        if (!array_key_exists($key, $this->config)) {
            $this->config[$key] = $this->modx->config[$key] ?? null;
        }
        $this->modx->config[$key] = $value;
    }

    /**
     * Compare the found classes regardless of their order.
     *
     * @param array $expected
     * @param array $actual
     * @param string $message
     */
    protected function assertClasses(array $expected, array $actual, string $message = '')
    {
        $this->assertEqualsCanonicalizing($expected, array_values($actual), $message);
    }
}
