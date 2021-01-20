<?php 

namespace EmailQueue\Controller\Component;

use EmailQueue\EmailQueue;
use Cake\Controller\Component;

class EmailComponent extends Component {
    public function enqueue ($to = [], $view_vars = [], $options = []) {
        $tmp = $to;
        if (is_array($tmp) && !empty($tmp['to'])) {
            $to = $tmp['to'];
            unset($tmp['to']);
        }
        if (!empty($tmp['template_vars']) && empty($view_vars)) {
            $view_vars = $tmp['template_vars'];
            unset($tmp['template_vars']);
        }
        if (!empty($tmp) && empty($options)) {
            $options = $tmp;
        }
        return EmailQueue::enqueue($to, $view_vars, $options);
    }
}

?>