<?php echo head(array('title' => __('CSV Import'))); ?>

<?php echo common('csvimport-nav'); ?>

<div id="primary">
    <h2><?php echo __('Logs for import #%s', $csvImport->id); ?></h2>

    <?php echo flash(); ?>

    <?php if (!empty($logs)): ?>
        <table class="simple" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th><?php echo __('Time'); ?></th>
                    <th><?php echo __('Priority'); ?></th>
                    <th><?php echo __('Message'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <?php echo html_escape(format_date($log->created, Zend_Date::DATE_SHORT)); ?>
                        <?php echo html_escape(format_date($log->created, Zend_Date::TIME_MEDIUM)); ?>
                    </td>
                    <td>
                        <?php
                            switch ($log->priority) {
                                case Zend_Log::EMERG:
                                    $priority = 'EMERGENCY';
                                    break;

                                case Zend_Log::ALERT:
                                    $priority = 'ALERT';
                                    break;

                                case Zend_Log::CRIT:
                                    $priority = 'CRITICAL';
                                    break;

                                case Zend_Log::ERR:
                                    $priority = 'ERROR';
                                    break;

                                case Zend_Log::WARN:
                                    $priority = 'WARNING';
                                    break;

                                case Zend_Log::NOTICE:
                                    $priority = 'NOTICE';
                                    break;

                                case Zend_Log::INFO:
                                    $priority = 'INFO';
                                    break;

                                case Zend_Log::DEBUG:
                                    $priority = 'DEBUG';
                                    break;

                                default:
                                    $priority = '';
                            }
                            echo $priority;
                        ?>
                    </td>
                    <td>
                        <?php
                            $param_arr = array($log->message);
                            $params = unserialize($log->params);
                            if (is_array($params)) {
                                $param_arr = array_merge($param_arr, $params);
                            }
                            echo html_escape(call_user_func_array('__', $param_arr));
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php echo __('You have no logs yet.'); ?></p>
    <?php endif; ?>
</div>

<?php
    echo foot();
?>
