<?php
namespace cURL\Tests;

use cURL;

class RequestsQueueTest extends TestCase
{
    /**
     * Test run queue without any requests
     */
    public function testSendNoRequests()
    {
        $bool = false;
        try {
            $q = new cURL\RequestsQueue();
            $q->send();
        } catch (cURL\Exception $ex) {
            $bool = true;
        }
        $this->assertTrue($bool);
    }

    /**
     * Test setDefaultOptions() and getDefaultOptions()
     */
    public function testOptions()
    {
        $q = new cURL\RequestsQueue();
        $opts = $q->getDefaultOptions();
        $this->assertInstanceOf('cURL\Options', $opts);
        $this->assertEmpty($opts->toArray());
        
        $opts = new cURL\Options();
        $opts->set(CURLOPT_URL, 'http://example-1/');
        $opts->set(CURLOPT_USERAGENT, 'browser');
        $q->setDefaultOptions($opts);
        $this->assertEquals($opts, $q->getDefaultOptions());
    }
    
    /**
     * Returns RequestsQueue for tests
     * 
     * @return cURL\RequestsQueue    Queue for tests
     */
    protected function prepareTestQueue()
    {
        $test = $this;
        $queue = new cURL\RequestsQueue();
        $queue->getDefaultOptions()
            ->set(CURLOPT_RETURNTRANSFER, true)
            ->set(CURLOPT_ENCODING, '');
        $queue->addListener(
            'complete',
            function (cURL\Event $event) use ($test) {
                $test->validateSuccesfulResponse($event->response, $event->request->_param);
            }
        );
        
        for ($i = 0; $i < 5; $i++) {
            $request = new cURL\Request();
            $request->_param = $i;
            $request->getOptions()->set(CURLOPT_URL, $this->createRequestUrl($i));
            $queue->attach($request);
        }
        
        $this->assertEquals(5, $queue->count());
        $this->assertEquals(5, count($queue));
        
        return $queue;
    }
    
    /**
     * Test request synchronous
     */
    public function testQueueSynchronous()
    {
        $queue = $this->prepareTestQueue();
        $queue->send();
    }
    
    /**
     * Test request asynchronous
     */
    public function testQueueAsynchronous()
    {
        $queue = $this->prepareTestQueue();
        
        while ($queue->socketPerform()) {
            $queue->socketSelect();
        }

        $e = null;
        try {
            $queue->socketPerform();
        } catch (cURL\Exception $e) {
        }
        
        $this->assertInstanceOf('cURL\Exception', $e);
    }
    
    /**
     * Test requests attaching on run time
     */
    public function testRepeatOnRuntime()
    {
        $n = 0;
        $queue = $this->prepareTestQueue();
        $queue->addListener(
            'complete',
            function (cURL\Event $event) use (&$n) {
                $n++;
                $request = $event->request;
                $queue = $event->queue;
                if (!isset($request->repeat)) {
                    $request->repeat = true;
                    $queue->attach($request);
                }
            }
        );
        $queue->send();
        $this->assertEquals(10, $n);
    }
    
    /**
     * Test requests attaching on run time
     */
    public function testAttachNewOnRuntime()
    {
        $total = 10;
        $test = $this;
        $queue = new cURL\RequestsQueue();
        $queue->getDefaultOptions()
            ->set(CURLOPT_RETURNTRANSFER, true)
            ->set(CURLOPT_ENCODING, '');
            
        
        $n = 0;
        $attachNew = function () use ($queue, &$n, $total) {
            if ($n < $total) {
                $n++;
                $request = new cURL\Request();
                $request->_param = $n;
                $request->getOptions()->set(CURLOPT_URL, $this->createRequestUrl($n));
                $queue->attach($request);
            }
        };
        
        $attachNew();
        $queue->addListener(
            'complete',
            function (cURL\Event $event) use (&$requests, $test, $attachNew) {
                $test->validateSuccesfulResponse($event->response, $event->request->_param);
                $attachNew();
            }
        );
        $queue->send();
        $this->assertEquals($total, $n);
    }
}
