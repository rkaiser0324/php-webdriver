<?php
/**
 * @file PHPUnitHelper.php
 *
 * Use PHPUnit to test whether a WebDriver interaction is
 * behaving as expected
 *
 * $Revision: 25827 $
 * $Date: 2011-06-02 11:46:39 -0700 (Thu, 02 Jun 2011) $
 */

class PHPUnitHelper
{
    protected $driver;

    protected $session;

    public static $implicit_wait;

    public function __construct($driver, $session) {
        echo "constructing PHPUnitHelper\n";
        $this->driver = $driver;
        $this->session = $session;
    }

    public static function implicit_wait($session,$timeout_msec) {
        $session->timeouts()->implicit_wait(array('ms' => $timeout_msec));
        PHPUnitHelper::$implicit_wait = $timeout_msec;
    }

    public function assert_title($expected_title_or_compare_func,$timeout_msec = "")
    {
        if ($timeout_msec == "") {
            $timeout_msec = PHPUnitHelper::$implicit_wait;
        }
        // Selenium has a built-in timeout (i.e. implicit
        // wait) but it only waits for elements to exist,
        // not for particular values.  So if we want to make
        // sure the title has a value, wait a bit for it.

        if (!is_callable($expected_title_or_compare_func,false,$expected_title))
        {
            // We didn't get a function to call so assume we
            // got a string which is the exact title to look
            // for
            $expected_title = $expected_title_or_compare_func;
        }
        echo "waiting " . $timeout_msec . " milliseconds for title to be \"" . $expected_title . "\"\n";
        $start_time = time();
        $end_time = $start_time + ($timeout_msec / 1000);
        do {
            $actual_title = $this->session->title();
            if ((is_callable($expected_title_or_compare_func) && $expected_title_or_compare_func($actual_title)) ||
                ($actual_title == $expected_title_or_compare_func))
            {
                return;
            }
        } while (time() < $end_time);
        PHPUnit_Framework_Assert::fail("Failed assertion: actual title <$actual_title> is not expected title <$expected_title>");
    }

    public function assert_url($expected_url)
    {
        $actual_url = $this->session->url();
        PHPUnit_Framework_Assert::assertTrue(($actual_url == $expected_url),
            "Failed assertion: actual url <$actual_url> is not expected url <$expected_url>");
    }

    // $locate_using and $locate_value describe the element,
    // the value of that element is different and isn't an
    // argument to this function...$compare_func probably
    // knows something about it, but that's different.
    public function wait_for_element_value($locate_using,$locate_value,$compare_func,$timeout_msec = "") {
        if ($timeout_msec == "") {
            $timeout_msec = PHPUnitHelper::$implicit_wait;
        }
        echo "waiting " . $timeout_msec . " milliseconds for \"" . $locate_using . ":" . $locate_value . "\" to be the desired value\n";
        $start_time = time();
        $end_time = $start_time + ($timeout_msec / 1000);
        $element = $this->session->element($locate_using,$locate_value);
        do {
            echo "getting value to check against\n";
            // GET of /value is deprecated, but getting the
            // value of the "value" attribute works.
            //$this_value = $element->getValue();
            $this_value = $element->attribute("value");
            if ($compare_func($this_value))
            {
                return;
            }
        } while (time() < $end_time);
        PHPUnit_Framework_Assert::fail("timeout waiting for value of element \"" . $locate_using . "\":\"" . $locate_value . "\", last value: \"" . $this_value . "\"");
    }
    
    public function safe_click($element,$expected_title_after_click,$timeout_msec = "") {
        // For some reason asking Selenium to click isn't
        // reliable.  Even when it responds with what looks
        // like a successful HTTP response, the browser
        // doesn't navigate to the page.
        echo "safe_click: get_class(\$element) is " . get_class($element) . "\n";
        $element->click();

        try
        {
            $this->assert_title($expected_title_after_click,$timeout_msec);
        }
        catch (Exception $ex)
        {
            // Failing once is OK.  Try clicking again.
            // Trouble is, our element may be considered
            // stale.  An element is really just an index
            // into a cache in selenium, and if the
            // cache is stale, we got problems.  This sort
            // of calls into question this entire method.
            // If the assert_title fails, then so be it.  If
            // the idea is that we're changing pages, that
            // may have happened after the first click()
            // above, but the assert_title may be failing
            // for some other reason.  Depending on the
            // specific exception we get, the most correct
            // thing to do may be to wait longer.  Until we
            // know, just re-throw to simulate this code not
            // really being here at all.
            //

            // But there's still the case where we click on
            // an element while a page is loading and that
            // click has no effect.  Which is why we need to
            // click again.  So if some kind of stale
            // element exception fires, the caller should
            // probably not be using this method.
            $element->click();

            // If this fails, we let the exception go.
            $this->assert_title($expected_title_after_click);
        }
    }
}
?>