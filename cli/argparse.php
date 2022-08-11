<?php
namespace pverb\cli;
require_once('color.php');

class ArgAction {
    private int $num_additional; // -1 is any # of args
    private Array $additional = [];
    private $callback;
    public function __construct($num_args, $cb) {
        $this->num_additional = $num_args;
        $this->callback = $cb;
    }
    public function num() { return $this->num_additional; }
    public function load_args(...$args) {
        $this->additional = $args;
    }
    public function __invoke() {
        if ($this->num_additional != -1 && count($this->additional) != $this->num_additional)
            throw new BadFunctionCallException('Argument count does not match the amount required by flag.');
        return $this->callback(...$this->additional);
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
            throw new InvalidArgumentException('Must include either a short or long flag.');
        $this->help = new ArgHelp($short, $long, $help);
        $this->short = $this->unflaggify($short);
        $this->long = $this->unflaggify($long);
        $this->act = new ArgAction($num_args, $cb);
    }
    public function get_act() { return $this->act; }

    public function get_flags() {
        return array_filter([$this->short, $this->long], function($f) { return $f != ""; });
    }
    public function get_help() {
        return $short.( $long != '' ? ','.$long : '').":\n\t".$help."\n\n";
    }
}

class ArgParser {
    private Array $registered_flags;
    private Array $extra_args = [];
    private $help = "-h, --help:\n\tPrints this help and exits.\n\n";
    public function add_arg(Argument $a) {
        $help .= $a->get_help();
        $flags = $a->get_flags();
        foreach ($flags as $f) { $registered_flags[$f] = $a->get_act(); }
    }

    public function parse(Array $args) {
        if (in_array('-h', $args) || in_array('--help', $args)) {
            $p = new Printer();
            $p->prnt($this->help);
            exit(0);
        }
        $actions = [];
        for ($i = 0; $i < count($args); $i++) {
            if ($args[$i][0] === '-') { // this is some argument
                if ($args[$i][1] === '-') { // long arg
                    $flag = $registered_flags[substr($args[$i], 2)];
                    $flag->load_args(array_slice($args, ++$i, $i + $flag->num()));
                    $actions[]=$flag;
                    $i += $flag->num();
                    continue;
                } else if (array_key_exists($args[$i][1], $registered_flags)) {
                    $flag = $registered_flags[$args[$i][1]];
                    if ($flag->num() == 0 && strlen($args[$i]) > 2) { 
                        // break out flags into list, e.g. -zxvf -> -z -x -v -f
                        array_splice($args, ++$i, 0, 
                            array_map(function($s) { return '-'.$s; },
                                array_slice(str_split($args[$i - 1]), 2)));
                    } else if (strlen($args[$i]) > 2) { // -llibstd, for example
                        $arg1 = substr($args[$i], 2);
                        $rest = array_slice($args, ++$i, $i + $flag->num() - 1);
                        $flag->load_args(array_merge([$arg1],$rest));
                        $actions[]=$flag;
                        $i += $flag->num();
                        continue;
                    }
                    $actions[]=$flag;
                } else {
                    throw new UnexpectedValueException('Unknown argument: '.$args[$i]);
                }
            } else $this->extra_args []=$args[$i];
        }
        return $actions;
    }
    public function get_extra_args() { return $this->extra_args(); }
}

?>
