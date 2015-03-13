<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Migration\Step;

/**
 * Class StepManagerTest
 */
class StepManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StepManager
     */
    protected $manager;

    /**
     * @var \Migration\Step\StepFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var \Migration\Logger\Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \Migration\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var \Migration\Step\ProgressStep|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $progress;

    public function setUp()
    {
        $this->factory = $this->getMockBuilder('\Migration\Step\StepFactory')->disableOriginalConstructor()
            ->setMethods(['getSteps', 'create'])
            ->getMock();
        $this->logger = $this->getMockBuilder('\Migration\Logger\Logger')->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMock();
        $this->config = $this->getMockBuilder('\Migration\Config')->disableOriginalConstructor()
            ->setMethods(['getSteps'])
            ->getMock();
        $this->progress = $this->getMockBuilder('\Migration\Step\ProgressStep')->disableOriginalConstructor()
            ->setMethods(['saveResult', 'isCompleted', 'clearLockFile', 'resetStep'])
            ->getMock();
        $this->manager = new StepManager($this->progress, $this->logger, $this->factory, $this->config);
    }

    public function testRunStepsIntegrityFail()
    {
        $this->setExpectedException('Migration\Exception', 'Integrity Check failed');
        $step = $this->getMockBuilder('\Migration\Step\StepInterface')->getMock();
        $step->expects($this->any())->method('getTitle')->will($this->returnValue('Title'));
        $step->expects($this->once())->method('integrity')->will($this->returnValue(false));
        $step->expects($this->never())->method('run');
        $step->expects($this->never())->method('volumeCheck');
        $this->progress->expects($this->any())->method('saveResult')->willReturnSelf();
        $this->progress->expects($this->any())->method('isCompleted')->willReturn(false);
        $this->config->expects($this->once())->method('getSteps')->willReturn([get_class($step)]);
        $this->factory->expects($this->once())->method('create')->with(get_class($step))
            ->will($this->returnValue($step));
        $this->assertSame($this->manager, $this->manager->runSteps());
    }

    public function testRunStepsVolumeFail()
    {
        $this->setExpectedException('Migration\Exception', 'Volume Check failed');
        $step = $this->getMockBuilder('\Migration\Step\StepInterface')->getMock();
        $step->expects($this->any())->method('getTitle')->will($this->returnValue('Title'));
        $step->expects($this->once())->method('integrity')->will($this->returnValue(true));
        $step->expects($this->once())->method('run')->will($this->returnValue(true));
        $step->expects($this->once())->method('volumeCheck')->will($this->returnValue(false));
        $step->expects($this->once())->method('rollback');
        $this->progress->expects($this->any())->method('saveResult')->willReturnSelf();
        $this->progress->expects($this->any())->method('isCompleted')->willReturn(false);
        $this->progress->expects($this->any())->method('resetStep')->with($step);
        $this->logger->expects($this->any())->method('info');
        $this->config->expects($this->once())->method('getSteps')->willReturn([get_class($step)]);
        $this->factory->expects($this->once())->method('create')->with(get_class($step))
            ->will($this->returnValue($step));
        $this->assertSame($this->manager, $this->manager->runSteps());
    }

    public function testRunStepsDataMigrationFail()
    {
        $this->setExpectedException('Migration\Exception', 'Data Migration failed');
        $step = $this->getMockBuilder('\Migration\Step\StepInterface')->getMock();
        $step->expects($this->any())->method('getTitle')->will($this->returnValue('Title'));
        $step->expects($this->once())->method('integrity')->will($this->returnValue(true));
        $step->expects($this->once())->method('run')->will($this->returnValue(false));
        $step->expects($this->never())->method('volumeCheck');
        $step->expects($this->once())->method('rollback');
        $this->progress->expects($this->any())->method('saveResult')->willReturnSelf();
        $this->progress->expects($this->any())->method('isCompleted')->willReturn(false);
        $this->progress->expects($this->any())->method('resetStep')->with($step);
        $this->logger->expects($this->any())->method('info');
        $this->config->expects($this->once())->method('getSteps')->willReturn([get_class($step)]);
        $this->factory->expects($this->once())->method('create')->with(get_class($step))
            ->will($this->returnValue($step));
        $this->assertSame($this->manager, $this->manager->runSteps());
    }

    public function testRunStepsSuccess()
    {
        $step = $this->getMockBuilder('\Migration\Step\StepInterface')->getMock();
        $step->expects($this->any())->method('getTitle')->will($this->returnValue('Title'));
        $step->expects($this->once())->method('integrity')->will($this->returnValue(true));
        $step->expects($this->once())->method('run')->will($this->returnValue(true));
        $step->expects($this->once())->method('volumeCheck')->will($this->returnValue(true));
        $this->progress->expects($this->any())->method('saveResult')->willReturnSelf();
        $this->progress->expects($this->any())->method('isCompleted')->willReturn(false);
        $this->progress->expects($this->once())->method('clearLockFile')->willReturnSelf();
        $this->logger->expects($this->at(0))->method('info')->with(PHP_EOL . "Title: integrity check");
        $this->logger->expects($this->at(1))->method('info')->with(PHP_EOL . "Title: data migration");
        $this->logger->expects($this->at(2))->method('info')->with(PHP_EOL . "Title: volume check");
        $this->logger->expects($this->at(3))->method('info')->with(PHP_EOL . "Migration completed");
        $this->config->expects($this->once())->method('getSteps')->willReturn([get_class($step)]);
        $this->factory->expects($this->once())->method('create')->with(get_class($step))
            ->will($this->returnValue($step));
        $this->assertSame($this->manager, $this->manager->runSteps());
    }

    public function testRunStepsWithSuccessProgress()
    {
        $step = $this->getMockBuilder('\Migration\Step\StepInterface')->getMock();
        $step->expects($this->any())->method('getTitle')->will($this->returnValue('Title'));
        $step->expects($this->never())->method('integrity');
        $step->expects($this->never())->method('run');
        $step->expects($this->never())->method('volumeCheck');
        $this->progress->expects($this->never())->method('saveResult');
        $this->progress->expects($this->any())->method('isCompleted')->willReturn(true);
        $this->progress->expects($this->once())->method('clearLockFile')->willReturnSelf();
        $this->logger->expects($this->at(0))->method('info')->with(PHP_EOL . "Title: integrity check");
        $this->logger->expects($this->at(1))->method('info')->with(PHP_EOL . "Title: data migration");
        $this->logger->expects($this->at(2))->method('info')->with(PHP_EOL . "Title: volume check");
        $this->logger->expects($this->at(3))->method('info')->with(PHP_EOL . "Migration completed");
        $this->config->expects($this->once())->method('getSteps')->willReturn([get_class($step)]);
        $this->factory->expects($this->once())->method('create')->with(get_class($step))
            ->will($this->returnValue($step));
        $this->assertSame($this->manager, $this->manager->runSteps());
    }
}
