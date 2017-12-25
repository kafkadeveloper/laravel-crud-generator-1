<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ViewScaffold extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view:scaffold';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Laravel CRUD Blade templates from database tables';

    public $timestamp = ['type' => 'hidden'];

    public $text = ['type' => 'textarea'];

    public $date = ['type' => 'date'];


    public $top = <<<EOT
@extends('layouts.app')
@section('content')
EOT;
    public $bottom = <<<EOT
@endsection
EOT;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach(DB::select('SHOW TABLES') as $table) {
            $table_name = object_get($table,"Tables_in_" . env('DB_DATABASE'));
            $data = [];
            foreach(DB::select("SHOW COLUMNS in $table_name") as $column) {
                //create a [field,type,maxlength,[options]] array for the create funcs
                //child of the data array passed to create funcs
                $child = array("field" => $column->Field);
                print_r($column);

                $type = explode(" ",$column->Type)[0];
                $eval = eval("return \$this->$type;");
                $child += $eval; //array_merge didnt work lmao
                $data[] = $child;
            }
            $path = base_path() . "/resources/views/" . $table_name;
            if(mkdir($path)) {
                file_put_contents($path . "/index.blade.php",$this->create_index($table_name,$data));
                file_put_contents($path . "/show.blade.php",$this->create_show($table_name,$data));
                file_put_contents($path . "/create.blade.php",$this->create_create($table_name,$data));
                file_put_contents($path . "/edit.blade.php",$this->create_update($table_name,$data));
            } else {
                echo "failed to create dir";
            }

        }
    }

    public function create_index($table_name,$data) {

        $singular = str_singular($table_name);
        $doc = new \DOMDocument();
        $container = $doc->createElement('div');
        $container->setAttribute('class','container');
        $row = $doc->createElement('div');
        $row->setAttribute('class','row');
        $col = $doc->createElement('div');
        $col->setAttribute('class','col-md-12');
        $panel = $doc->createElement('div');
        $panel->setAttribute('class','panel');
        $panelheading = $doc->createElement('div');
        $panelheading->setAttribute('class','panel-heading');
        $h1 = $doc->createElement('h1',ucfirst($table_name));
        $panelheading->appendChild($h1);
        $panelbody = $doc->createElement('div');
        $panelbody->setAttribute('class','panel-body');

        $table = $doc->createElement('table');
        $table->setAttribute('class','table table-responsive table-hover');

        $foreach = $doc->createTextNode("@foreach(\$$table_name as \$$singular)");
        $endforeach = $doc->createTextNode('@endforeach');

        $thead = $doc->createElement('thead');

        foreach($data as $column) {
            $th = $doc->createElement('th',$column['field']);
            $thead->appendChild($th);
        }
        $tbody = $doc->createElement('tbody',"@foreach(\$$table_name as \$$singular)");
        $tr = $doc->createElement('tr');
        foreach($data as $column) {
            $field = $column['field'];
            $td = $doc->createElement('td',"{{ \$$singular->$field }}");
            $tr->appendChild($td);
        }
        $tbody->appendChild($tr);
        $tbody->appendChild($endforeach);

        $table->appendChild($thead);
        $table->appendChild($tbody);

        $panelbody->appendChild($table);

        $panel->appendChild($panelheading);
        $panel->appendChild($panelbody);

        $col->appendChild($panel);
        $row->appendChild($col);
        $container->appendChild($row);
        $doc->appendChild($container);
        $doc->formatOutput = true;

        $raw = $doc->saveHTML();
        $greater = preg_replace("/&gt;/",'>',$raw);
        $lesser = preg_replace('/&lt;/','<',$greater);


        return $this->top . $lesser . $this->bottom;


    }

    public function create_create($table_name,$data) {

        $singular = str_singular($table_name);
        $doc = new \DOMDocument();
        $container = $doc->createElement('div');
        $container->setAttribute('class','container');
        $row = $doc->createElement('div');
        $row->setAttribute('class','row');
        $col = $doc->createElement('div');
        $col->setAttribute('class','col-md-12');
        $panel = $doc->createElement('div');
        $panel->setAttribute('class','panel');
        $panelheading = $doc->createElement('div');
        $panelheading->setAttribute('class','panel-heading');
        $h1 = $doc->createElement('h1',ucfirst($table_name));
        $panelheading->appendChild($h1);
        $panelbody = $doc->createElement('div');
        $panelbody->setAttribute('class','panel-body');

        $form = $doc->createElement('form');
        $form->setAttribute('action',"/$table_name");
        $form->setAttribute('method','POST');

        foreach($data as $column) {
            $fg = $doc->createElement('div');
            $fg->setAttribute('class','form-group');
            $label = $doc->createElement('label',ucfirst($column['field']));
            $fg->appendChild($label);
            switch($column['type']) {
                case "text":
                    $input = $doc->createElement('input');
                    $input->setAttribute('name',$column['field']);
                    $input->setAttribute('class','form-control');
                    //$input->setAttribute('maxlength',$column['length'] || "");
                    $input->setAttribute('type','text');
                    $fg->appendChild($input);
                    break;
                case "number":
                    $input = $doc->createElement('input');
                    $input->setAttribute('type','number');
                    $input->setAttribute('class','form-control');
                    $fg->appendChild($input);
                    break;
                case "select":
                    $select = $doc->createElement('select');
                    foreach($column['options'] as $option) {
                        $opt = $doc->createElement('option',ucfirst($option));
                        $opt->setAttribute('value',$option);

                        $select->appendChild($opt);
                    }
                    $select->setAttribute('class','form-control');
                    $select->setAttribute('name',$column['field']);
                    $fg->appendChild($select);
                    break;
                case "date":
                    $input = $doc->createElement('input');
                    $input->setAttribute('type','date');
                    $input->setAttribute('class','form-control');
                    $fg->appendChild($input);
                    break;
                case "textarea":
                    $textarea = $doc->createElement('textarea');
                    $textarea->setAttribute('class','form-control');
                    $fg->appendChild($textarea);
                    break;
                default:
                    break;
            }
            $form->appendChild($fg);
        }

        $submit = $doc->createElement('button','Submit');
        $submit->setAttribute('type','submit');
        $form->appendChild($submit);
        $panelbody->appendChild($form);
        $panel->appendChild($panelbody);
        $col->appendChild($panel);
        $row->appendChild($col);
        $container->appendChild($row);
        $doc->appendChild($container);
        return $this->top . $doc->saveHTML() . $this->bottom;


    }
    public function create_update($table_name,$data) {

        $singular = str_singular($table_name);
        $doc = new \DOMDocument();
        $container = $doc->createElement('div');
        $container->setAttribute('class','container');
        $row = $doc->createElement('div');
        $row->setAttribute('class','row');
        $col = $doc->createElement('div');
        $col->setAttribute('class','col-md-12');
        $panel = $doc->createElement('div');
        $panel->setAttribute('class','panel');
        $panelheading = $doc->createElement('div');
        $panelheading->setAttribute('class','panel-heading');
        $h1 = $doc->createElement('h1',ucfirst($table_name));
        $panelheading->appendChild($h1);
        $panelbody = $doc->createElement('div');
        $panelbody->setAttribute('class','panel-body');

        $form = $doc->createElement('form');
        $form->setAttribute('action',"/$table_name/" . "{{ $singular" . '["id"]' . "}}");
        $form->setAttribute('method','POST');
        $csrf = $doc->createTextNode("{{ csrf_field() method_field('PUT') }}");
        $form->appendChild($csrf);

        foreach($data as $column) {
            $fg = $doc->createElement('div');
            $fg->setAttribute('class','form-group');
            $label = $doc->createElement('label',ucfirst($column['field']));
            $fg->appendChild($label);
            switch($column['type']) {
                case "text":
                    $input = $doc->createElement('input');
                    $input->setAttribute('name',$column['field']);
                    $input->setAttribute('class','form-control');
                    //$input->setAttribute('maxlength',$column['length']);
                    $input->setAttribute('type','text');
                    $fg->appendChild($input);
                    break;
                case "number":
                    $input = $doc->createElement('input');
                    $input->setAttribute('type','number');
                    $input->setAttribute('class','form-control');
                    $input->setAttribute('name',$column['field']);
                    $fg->appendChild($input);
                    break;
                case "select":
                    $select = $doc->createElement('select');
                    foreach($column['options'] as $option) {
                        $opt = $doc->createElement('option',ucfirst($option));
                        $opt->setAttribute('value',$option);

                        $select->appendChild($opt);
                    }
                    $select->setAttribute('class','form-control');
                    $select->setAttribute('name',$column['field']);
                    $fg->appendChild($select);
                    break;
                case "date":
                    $input = $doc->createElement('input');
                    $input->setAttribute('type','date');
                    $input->setAttribute('class','form-control');
                    $input->setAttribute('name',$column['field']);
                    $fg->appendChild($input);
                    break;
                case "textarea":
                    $textarea = $doc->createElement('textarea');
                    $textarea->setAttribute('class','form-control');
                    $textarea->setAttribute('name',$column['field']);
                    $fg->appendChild($textarea);
                    break;
                default:
                    break;
            }
            $form->appendChild($fg);
        }
        $submit = $doc->createElement('button','Submit');
        $submit->setAttribute('type','submit');
        $form->appendChild($submit);
        $panelbody->appendChild($form);
        $panel->appendChild($panelbody);
        $col->appendChild($panel);
        $row->appendChild($col);
        $container->appendChild($row);
        $doc->appendChild($container);
        return $this->top . $doc->saveHTML() . $this->bottom;


    }

    public function create_show($table_name,$data) {

        $singular = str_singular($table_name);
        $doc = new \DOMDocument();
        $container = $doc->createElement('div');
        $container->setAttribute('class','container');
        $row = $doc->createElement('div');
        $row->setAttribute('class','row');
        $col = $doc->createElement('div');
        $col->setAttribute('class','col-md-12');
        $panel = $doc->createElement('div');
        $panel->setAttribute('class','panel');
        $panelheading = $doc->createElement('div');
        $panelheading->setAttribute('class','panel-heading');
        $h1 = $doc->createElement('h1',ucfirst(str_singular($table_name)) . " details");
        $panelheading->appendChild($h1);
        $panelbody = $doc->createElement('div');
        $panelbody->setAttribute('class','panel-body');

        foreach($data as $column) {
            $h3 = $doc->createElement('h3',$column['field']);
            $field = $column['field'];
            $p = $doc->createElement('p',"{{ \$$table_name->$field }}");
            $panelbody->appendChild($h3);
            $panelbody->appendChild($p);
        }
        $panel->appendChild($panelheading);
        $panel->appendChild($panelbody);
        $col->appendChild($panel);
        $row->appendChild($col);
        $container->appendChild($row);
        $doc->appendChild($container);


        $raw = $doc->saveHTML();
        $greater = preg_replace("/&gt;/",'>',$raw);
        $lesser = preg_replace('/&lt;/','<',$greater);


        return $this->top . htmlspecialchars_decode($lesser) . $this->bottom;



    }

    public function enum(...$params) {
        return ['type' => 'select', 'options' => func_get_args()];
    }
    public function varchar($maxlength) {
        return ['type' => 'text', 'length' => $maxlength];
    }
    public function int() {
        return ["type" => 'number'];
    }
    public function decimal() {
        return ['type' => 'text'];
    }
}
