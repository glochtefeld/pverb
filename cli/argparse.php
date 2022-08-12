<?php
namespace pverb\cli;
use \Exception;
require_once('color.php');

class ArgAction {
    private int $num_additional; 
    private Array $additional = [];
    private $callback;
    public function __construct($num_args, $cb) {
        // Note: planned feature is using -1 to have n arguments. Need
        // to update parser to support this.
        if ($num_args < 0)
            throw new \InvalidArgumentException('Cannot have fewer than 0 arguments on a flag!');
        $this->num_additional = $num_args;
        $this->callback = $cb;
    }
    public function num() { return $this->num_additional; }
    public function load_args(...$args) {
        $this->additional = $args;
    }
    public function __invoke() {
        if ($this->num_additional != -1 && count($this->additional) != $this->num_additional) {
            $st = print_r($this->additional, true);
            throw new \BadFunctionCallException('Argument count does not match the amount required by flag: Expected '.$this->num_additional.' but got '.count($this->additional).'). They were: '.$st);
        }
        call_user_func($this->callback, ...$this->additional);
    }
}
class Argument {
    private string $short;
    private string $long;
    private string $help;

    private ArgAction $act;

    private function unflaggify(string $f) { return preg_replace('/^-+/', '', $f); }
    public function __construct(string $short="", string $long="", string $help="", $num_args=0, $cb=null) {
        if ($short === "" && $long === "") 
            throw new \InvalidArgumentException('Must include either a short or long flag.');
        $this->help = $help;
        $this->short = $this->unflaggify($short);
        $this->long = $this->unflaggify($long);
        $this->act = new ArgAction($num_args, $cb);
    }
    public function get_act() { return $this->act; }

    public function get_flags() {
        return array_filter([$this->short, $this->long], function($f) { return $f != ""; });
    }
    public function get_help() {
        return ($this->short != '' ? '-'.$this->short : '')
            .(count($this->get_flags()) > 1 ? ', ':'')
            .( $this->long != '' ? '--'.$this->long : '')
            .":\n\t".$this->help."\n\n";
    }
}

class ArgParser {
    private Array $registered_flags;
    private Array $extra_args = [];
    private $help = "-h, --help:\n\tPrints this help and exits.\n\n";
    public function add_arg(Argument $a) {
        $this->help .= $a->get_help();
        $flags = $a->get_flags();
        foreach ($flags as $f) { $this->registered_flags[$f] = $a->get_act(); }
    }

    public function parse(Array $args) {
        if (in_array('-h', $args) || in_array('--help', $args)) {
            $p = new Printer();
            $p->prnt($this->help);
            exit(0);
        }
        $args = array_slice($args, 1);
        $actions = [];
        for ($i = 0; $i < count($args); $i++) {
            if ($args[$i][0] === '-') { // this is some argument
                if ($args[$i][1] === '-' && array_key_exists(substr($args[$i], 2), $this->registered_flags)) { // long arg
                    $flag = $this->registered_flags[substr($args[$i], 2)];
                    $flag->load_args(...array_slice($args, ++$i, $flag->num()));
                    $actions[]=$flag;
                    $i += max(0, $flag->num() - 1);
                    continue;
                } else if (array_key_exists($args[$i][1], $this->registered_flags)) {
                    $flag = $this->registered_flags[$args[$i][1]];
                    if ($flag->num() == 0 && strlen($args[$i]) > 2) { 
                        // break out flags into list, e.g. -zxvf -> -z -x -v -f
                        array_splice($args, ++$i, 0, 
                            array_map(function($s) { return '-'.$s; },
                                array_slice(str_split($args[$i - 1]), 2)));
                    } else if ($flag->num() != 0 && strlen($args[$i]) > 2) { // -llibstd, for example
                        $arg1 = substr($args[$i], 2);
                        $rest = $flag->num() != 1 ? array_slice($args, ++$i, $flag->num()) : [];
                        $flag->load_args($arg1, ...$rest);
                        $actions[]=$flag;
                        $i += ($flag->num() - 1);
                        continue;
                    } else if ($flag->num() != 0 && strlen($args[$i]) == 2) { // -c 4, for example
                        $load = array_slice($args, ++$i, $flag->num());
                        $flag->load_args(...$load);
                        $i += ($flag->num() - 1);
                    }
                    $actions[]=$flag;
                } else {
                    throw new \UnexpectedValueException('Unknown flag: '.$args[$i]);
                }
            } else $this->extra_args []=$args[$i];
        }
        return $actions;
    }
    public function get_extra_args() { return $this->extra_args(); }
}

if (__FILE__ == get_included_files()[0]) {
    $ap = new ArgParser();
    $counter = 0;
    $printer = new Printer();

    try {
        $ap->add_arg(new Argument('-t', '--test', 'This is a test flag to check if arguments even work.', 0, function() use ($printer) {
            $printer->print_col("Hello, world!", Color::C_GREEN);
        }));
        $ap->add_arg(new Argument('-p', '--print', 'Prints the next argument to stdout.', 1, function(string $a) {
            echo $a."\n";
        }));
        $ap->add_arg(new Argument('', '--add-one', 'Returns the successor of the next argument.', 1, function($n) use (&$counter) {
            $counter = intval($n) + 1;
        }));
    } catch (\InvalidArgumentException $e) {
        $printer->print_col($e->getMessage(), Color::C_RED);
        exit(0);
    }


    try {
        $actions = $ap->parse($argv);
    } catch (\UnexpectedValueException $e) {
        $printer->print_col($e->getMessage(), Color::C_RED);
        exit(0);
    }

    $had_err = false;
    foreach ($actions as $a)
        try {
            $a();
        } catch (\BadFunctionCallException $e) {
            $printer->print_col($e->getMessage(), Color::C_RED);
            $had_err = true;
        }
    if ($had_err) exit(0);

    print 'Counter = '.$counter."\n";
}

?>
