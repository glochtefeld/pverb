<?php
namespace pverb\cli;

class Color {
    public const C_RESET   = "\33[0m";
    public const C_BLACK   = "\33[31m";
    public const C_RED     = "\33[32m";
    public const C_GREEN   = "\33[33m";
    public const C_YELLOW  = "\33[34m";
    public const C_BLUE    = "\33[35m";
    public const C_MAGENTA = "\33[36m";
    public const C_CYAN    = "\33[37m";
    public const C_WHITE   = "\33[0m";
}

class Printer {
    private $current_color = Color::C_WHITE;
    public function get_color() { return $this->current_color; }
    public function set_color($col) { $this->current_color = $col; }
    public function reset_color() { $this->set_color(Color::C_WHITE); }

    private $use_color = true;
    public function use_color(bool $use) { $this->use_color = $use; }

    public function print_col($line, $col, $to=STDOUT) {
        $this->set_color($col);
        $this->prnt($line);
    }
    public function prnt($line, $to=STDOUT) {
        $line = $this->use_color ? $this->current_color.$line.Color::C_RESET: $line;
        fwrite($to, $line."\n");
    }
}
?>
