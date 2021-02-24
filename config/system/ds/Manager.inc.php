<?php
class Manager extends Ds
{
    public function __construct() {
    }
    public function Structure($_command,$_ArgValue)
    {
        switch ($_ArgValue) {
            case COMMAND_CONTROLLER:
                Executor::Controller($_command);
            break;
            case COMMAND_MODEL:
                Executor::Model($_command);
            break;
            case COMMAND_VIEW:
                Executor::View($_command);
            break;
            case COMMAND_API:
                Executor::Api($_command);
            break;
            case COMMAND_TEST:
                Executor::Test($_command);
            break;
            default:
            msg('Command '.$_ArgValue.' not found!');
                break;
        }
    }
}
