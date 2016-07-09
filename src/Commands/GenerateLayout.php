<?php

namespace Misma\Bootscraper\Commands;

use Config;
use Illuminate\Console\Command;

class GenerateLayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bootscraper:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates layouts based on Bootscraper config file';

    private $MenuCounter = 1;
    private $menu_LinksToKeep = 10;
    private $navMatchReq = .001;
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
        
        /*
        *  Check configuration values/settings
        *  Throw error and die if there is an error in these
        */
            if(!file_exists(config_path() . "/bootscraper.php")){
                $this->call('vendor:publish');
                $this->error(" FATAL - No Config                      ");
                $this->error(" Please check configuration file. E01-1 ");
                exit;
            }

            if(!$func_arg['layoutname'] = str_slug(Config::get('bootscraper.layout_name'))){
                $this->error(" FATAL - Bad Config                     ");
                $this->error(" Please check configuration file. E02-1 ");
                exit;
            }

            // Grab The template name from the config file and check for validity
            $args['template'] = Config::get('bootscraper.template_dir');
            if(!$args['template']){
                $this->error(" FATAL - Bad Config                     ");
                $this->error(" Please check configuration file. E02-2 ");
                exit;
            }

        // Enter an empty line for display formatting
        $this->line("");

        // temporary arrays that will hold DOM data at various points
        $func_arg['index'] = null;
        $func_arg['force_nonav'] = null;

        $Array["HeadTitle"] = null;
        $Array["HeadMeta"] = [];
        $Array["HeadCSS"] = [];
        $Array["HeadScripts"] = [];

        $Array["BodyNav"] = null;
        $Array["BodyScripts"] = null;
        $Array["AssetFolders"] = [];

        
        // Publish our config file if it doesn't exist

        // Get options from Config
        $args['options'] = ["footer"=>Config::get('bootscrape.replace_footer')];

        libxml_use_internal_errors(true);  # Suppress some errors

        // Fetch all files in the template folder, prepend some common index files
        $files = ["index.html", "index-2.html", "index-3.html"];
        $files = (array_merge($files, scandir($args['template'])));
        
        // Get a list of public directories that will be moved.
        // All folders in the root dir
        foreach($files as $key=>$file){
            if($file=="."||$file==".."){
                unset($files[$key]);
                continue;
            }
            
            if(is_dir($args['template'] . "/" . $file)){
                $Array['AssetFolders'][] = $file;
                unset($files[$key]);
            }

            if(!file_exists($args['template'] . "/" . $file)){
                unset($files[$key]);
            }
        }
        $files = array_unique($files);

        // Loop over the files in the dir
        foreach($files as $file){

            if(strpos($file, "ndex")){
               $func_arg['index'] = $file; 
            }
            

            if($file=="."||$file==".."||!strpos($file, ".html")||is_dir($args['template'] . "/" . $file)) continue;

            $content = file_get_contents($args['template'] . "/" . $file);
            $OriginalDom = new \DOMDocument();
            $OriginalDom->loadHTML($content);
            $OriginalXpath = new \DOMXPATH($OriginalDom);

            /*
            *   HEAD SECTION
            */
            $OriginalHead = $OriginalDom->getElementsByTagName('head')[0];
            $OriginalHeadHTML = $OriginalDom->saveXML($OriginalHead);

                $OriginalHead_css = $OriginalHead->getElementsByTagName("link");
                $OriginalHead_title = $OriginalHead->getElementsByTagName("title");
                $OriginalHead_scripts = $OriginalHead->getElementsByTagName("script");
                $OriginalHead_meta = $OriginalHead->getElementsByTagName("meta");
            
                foreach($OriginalHead_scripts as $tag){
                    $this->inc($Array["HeadScripts"][$OriginalDom->saveXML($tag)]);
                }

                foreach($OriginalHead_title as $tag){
                    $Array["HeadTitle"] = $OriginalDom->saveXML($tag);
                }

                foreach($OriginalHead_css as $tag){
                    $this->inc($Array["HeadCSS"][$OriginalDom->saveXML($tag)]);
                }

                foreach($OriginalHead_meta as $tag){
                    $this->inc($Array["HeadMeta"][$OriginalDom->saveXML($tag)]);
                }

            
            /*
            *   NAVIGATION
            */
            $OriginalBody = $OriginalDom->getElementsByTagName('body')[0];
            $OriginalBodyHTML = $OriginalDom->saveXML($OriginalBody);
            
            $OriginalBody_scripts = $OriginalBody->getElementsByTagName("script");
                foreach($OriginalBody_scripts as $tag){
                    $this->inc($Array["BodyScripts"][$OriginalDom->saveXML($tag)]);
                }
                
                $homelinks = $OriginalXpath->query("//a[contains(@href,'index')][contains(@href,'.html')]");
                $navNode = [];
                $navNodeParent = [];

                $ElementsToSkip = [];

                foreach($homelinks as $link){
                    $elementstoskip_this = [];
                    $navMatch = 0;
                    $search_depth = 12;
                    $currentNode = $link;

                    // Check for siblings links
                    $siblings = $link->parentNode->parentNode->parentNode->getElementsByTagName("a");
                    foreach($siblings as $sibling){
                        foreach ($files as $filename){
                            // $hit = strpos(">".$sibling->getAttribute("href"), $filename);
                            // echo $sibling->getAttribute("href") . " - " . $filename . " = " . "$hit\n";
                            if(strpos(">".$sibling->getAttribute("href"), $filename)){
                                $navMatch++;
                            }
                        }
                    }

                    if($navMatch < ($this->navMatchReq * count($files))){
                        $this->error(" FATAL - Parse Error         ");
                        $this->error(" Not enough nav links. E05-1 ");
                        continue;
                    }

                    while($search_depth > 0){
                        if(!$currentNode){
                            $search_depth = 0;
                            $this->logError("No Node");
                            continue;
                        }

                        $currentParent = $currentNode->parentNode;
                        if(!$currentParent){
                            $search_depth = 0;
                            $currentNode = $currentParent;
                            $this->logError("No Parent Node");
                            continue;
                        }
                        
                        // If no parent exists, continue
                        if($currentParent->nodeType !== XML_ELEMENT_NODE){
                            $this->logError("Parent Not element");
                            $search_depth = 0;
                            $currentNode = $currentParent;
                            continue;
                        }

                        $currentNode->setAttribute("dont-remove", "1");

                        if($currentNode->nodeName ==  "ul"){
                            $navNode[] = $currentNode;
                            $navNodeParent[] = $currentNode->parentNode;
                        }

                        $ElementsToSkip[] = $currentNode;
                        $currentNode = $currentParent;
                    }
                }

                if(count($navNode) == 0){
                    if(!$func_arg['force_nonav']){
                        $this->question("IRREGULAR: No navigation block found");
                        $func_arg['force_nonav'] = $this->confirm('Do you wish to continue anyway? [y|N]');
                        if (!$func_arg['force_nonav']){
                            $this->line("exiting...");
                            exit;
                        }
                    }
                }elseif(count($navNode)>0){
                    $nav_class = str_replace(" ", ".", end($navNode)->getAttribute("class"));
                    $n = end($navNode);
                    while($n->childNodes->length > $this->menu_LinksToKeep){
                        $n->removeChild($n->childNodes[$n->childNodes->length-1]);
                    }
                    $this->MenuCounter = 1;
                    $this->replaceMenuLinks($n->childNodes, "Menu Item");

                    // end($navNode)->appendChild($l2);;
                    $Array['BodyNav'] = $n;
                }
                // Force exit after procession only on file. To be worked on later
                break;

        }


        // Reset array keys
        $files = array_values($files);
        // If not 'index' file is found, choose a random file
        if(!$func_arg['index']){
            $r = 0;
            while($r<2 && !is_dir($files[$r])){
                $r = array_rand($files);
            }
            $func_arg['index'] = $files[$r]; 
        }

        libxml_clear_errors();
        
        

        // *****************************
        // BEGIN GENERATING LAYOUT FILES
        // *****************************
        if(!is_dir("public/".$func_arg['layoutname'])){
            \File::makeDirectory("public/".$func_arg['layoutname'], 0777, true);
        }

        $this->info("Generating layout (".$func_arg['layoutname'].")...");

        // Ensure all views exits
            $dir_views = base_path() . "/resources/views";
            $dir_layouts = $dir_views . "/layouts";
            $dir_menus = $dir_views . "/menus";

            if(file_exists($dir_layouts . "/layout-" . $func_arg['layoutname'] . ".blade.php")){
                if($this->confirm('"' . $func_arg['layoutname'] . '" already exists. Do you wish to continue and OVERWRITE anyway? [y|N]')){

                }else{
                    $this->error("Exiting. Please change 'layout_name' in config file and try again");
                    exit;
                }
            }

            if(!is_dir($dir_layouts)){
                @\File::makeDirectory($dir_layouts, 0777, true);
            }
            if(!is_dir($dir_menus)){
                @\File::makeDirectory($dir_menus, 0777, true);
            }

        // Copy assets folders
            foreach($Array['AssetFolders'] as $folder){
                $this->movedir($args['template'] . "/$folder", public_path() . "/" . $func_arg['layoutname'] . "/$folder");
            }
            
        // Get file names
            $file_layout = $dir_layouts . "/layout-" . $func_arg['layoutname']. ".blade.php";
            $file_welcome = $dir_views . "/landing.blade.php";
            $file_menu = $dir_menus . "/menu-" . $func_arg['layoutname']. ".blade.php";

        
        // Begin DOM Generation and writing            
            // Create Menu file
            $NewMenuDom = new \DOMDocument;
            $NewMenuDom->formatOutput = true;
            $NewMenuDom->appendChild($NewMenuDom->importNode($Array['BodyNav'], true));
            $NewMenuDom->saveHTMLFile($file_menu);

            $menuname = "menus.menu-" . $func_arg['layoutname'];
            $Array['BodyNav']->parentNode->nodeValue = "@include" . '("' . $menuname . '")';
        
            // Create layout document
            $NewDom = new \DOMDocument;
            $NewDom->formatOutput = true;
            $NewDom->saveHTMLFile($file_layout);

            $html_dom_new = $NewDom->createElement('html');
            
            # Head Section
                $head_dom_new = $NewDom->createElement('head');
                
                $fragment_title = $NewDom->createDocumentFragment();
                $Array['HeadTitle'] = preg_replace('/<title>(.*?)<\/title>/', '<title>Laravel App :: Powered by Bootscraper</title>', $Array['HeadTitle']);
                $fragment_title->appendXML($Array['HeadTitle']);
                $head_dom_new->appendChild($fragment_title);

                foreach($Array['HeadMeta'] as $meta=>$count){
                    $link = $NewDom->createDocumentFragment();
                    $link->appendXML($meta);
                    $head_dom_new->appendChild($link);
                }

                // exit;
                foreach($Array['HeadCSS'] as $meta=>$count){
                    
                    $href_pattern = '/<link(.+)href="(.*)"/';
                    preg_match($href_pattern, $meta, $match);
                    
                    foreach ($Array['AssetFolders'] as $folder) {
                        if(isset($match[count($match)-1]) && !(strpos(">".$match[count($match)-1], $folder."/")===false)){
                            $st1 = strpos($meta, "href") + 6;
                            $st2 = strpos($meta, '"', $st1);
                            $link_n = substr($meta, $st1, $st2-$st1);
                            $meta = str_replace($link_n, $func_arg['layoutname'] . "/" . $link_n, $meta);
                        }
                    }

                    $link = $NewDom->createDocumentFragment();
                    $link->appendXML($meta);
                    $head_dom_new->appendChild($link);
                }

                foreach($Array['HeadScripts'] as $meta=>$count){
                    
                    $href_pattern = '/<script(.+)src="(.*)"/';
                    preg_match($href_pattern, $meta, $match);
                    
                    foreach ($Array['AssetFolders'] as $folder) {
                        if(isset($match[count($match)-1]) && !(strpos(">".$match[count($match)-1], $folder."/")===false)){
                            $st1 = strpos($meta, "src") + 5;
                            $st2 = strpos($meta, '"', $st1);
                            $link_n = substr($meta, $st1, $st2-$st1);
                            $meta = str_replace($link_n, $func_arg['layoutname'] . "/" . $link_n, $meta);                            
                        }
                    }

                    $link = $NewDom->createDocumentFragment();
                    $link->appendXML($meta);
                    $head_dom_new->appendChild($link);
                }

            # Body Section
                $body_dom_new = $NewDom->createElement("body");
                // Find content div using commonly used identifiers (ids and classes)
                
                // "content"?
                    # has class
                    $results = $OriginalXpath->query('//*[contains(concat(" ", @class, " "), " content ")]');
                
                    # has ID
                    if(!$results || $results->length < 1){
                        $results = $OriginalXpath->query('//*[contains(concat(" ", @id, " "), " content ")]');
                    }

                // content-wrapper
                    # has class
                    if(!$results || $results->length < 1){
                        $results = $OriginalXpath->query('//*[contains(concat(" ", @class, " "), " content-wrapper ")]');
                    }

                // "wrapper"
                    # has class?
                    if(!$results || $results->length < 1){
                        $results = $OriginalXpath->query('//*[contains(concat(" ", @class, " "), " wrapper ")]');
                    }
                    #has ID
                    if(!$results || $results->length < 1){
                        $results = $OriginalXpath->query('//*[contains(concat(" ", @id, " "), " wrapper ")]');
                    }

                // "page-wrapper"
                    # has class?
                    if(!$results || $results->length < 1){
                        $results = $OriginalXpath->query('//*[contains(concat(" ", @class, " "), " page-wrapper ")]');
                    }
                    #has ID
                    if(!$results || $results->length < 1){
                        $results = $OriginalXpath->query('//*[contains(concat(" ", @id, " "), " page-wrapper ")]');
                    }

                // "main-wrapper"
                    # has class?
                    if(!$results || $results->length < 1){
                        $results = $OriginalXpath->query('//*[contains(concat(" ", @class, " "), " main-wrapper ")]');
                    }
                    #has ID
                    if(!$results || $results->length < 1){
                        $results = $OriginalXpath->query('//*[contains(concat(" ", @id, " "), " main-wrapper ")]');
                    }



                if(!$results || $results->length < 1){
                    $this->error(" FATAL - Parse Error             ");
                    $this->error(" Cannot find content area. E04-1 ");
                    exit; 
                }

                $content_div = &$results[0];

                $loop_curr = $content_div;

                $blank_content_container = $this->getContentContainer($content_div);

                foreach($blank_content_container->childNodes as $child){
                    if($child->nodeType == 1){
                        $blank_content_container->removeChild($child);  
                    }
                }

                while ($blank_content_container->childNodes->length > 0) {
                    $blank_content_container->removeChild($blank_content_container->childNodes[0]);
                }

                $blank_content_container = $blank_content_container;

                while($blank_content_container->childNodes->length > 0){
                    $blank_content_container->removeChild($blank_content_container->childNodes[0]);
                }
                $blank_content_container->nodeValue = '@yield("Content")';
                
                $body_dom_new = $OriginalBody;
                $this->MenuCounter = 1;
                $this->replaceMenuLinks($body_dom_new->childNodes, false);

                $all_images = $body_dom_new->getElementsByTagName("img");
                foreach($all_images as $node){
                    if($node->nodeName == "img"){
                        foreach ($Array['AssetFolders'] as $folder) {
                            if(!(strpos(">".$node->getAttribute("src"), $folder."/")===false)){
                                $node->setAttribute("src", $func_arg['layoutname']."/".$node->getAttribute("src"));
                            }
                        }
                    }
                }
                
                foreach ($body_dom_new->childNodes as $bdom) {
                    if($bdom->nodeName == "script"){
                        if(strlen($bdom->getAttribute("src")) > 4){
                            foreach ($Array['AssetFolders'] as $folder) {
                                if(!(strpos(">".$bdom->getAttribute("src"), $folder."/")===false)){
                                    $bdom->setAttribute("src", $func_arg['layoutname']."/".$bdom->getAttribute("src"));
                                }
                            }
                        }
                    }
                } 

                // Add route to route file
                // TODO: Avoid duplication if route exists
                $route_str = "Route::get('/', function () {return view('bootscrape-welcome');});";
                $fh = fopen("app/Http/routes.php", "a");
                fwrite($fh, $route_str);
                fclose($fh);

                $html_dom_new->appendChild($head_dom_new);
                $html_dom_new->appendChild($NewDom->importNode($body_dom_new, true));
                $NewDom->appendChild($html_dom_new);

                $NewDom->saveHTMLFile($file_layout);

            $this->updateWelcomeFile($func_arg['layoutname']);

        $meta = str_replace($link_n, $func_arg['layoutname'] . "/" . $link_n, $meta);                            

        $this->line("Done. Go build.");
        exit;
    }

    private function inc(&$var){
        if (isset($var)) $var++;else $var=1;
    }

    private function replaceMenuLinks(&$nodes, $handleText){
        foreach($nodes as $node){
            if($node->nodeName == "a"){
                $node->setAttribute("href", "#");

                    foreach($node->childNodes as $chnd){
                        if($chnd->nodeType !== 3){
                            if($chnd->nodeName=="span"){
                                $chnd->nodeValue = "Home";
                            }
                            $node->removeChild($chnd);
                        }else{
                            if(strlen(trim(str_replace(["\r", "\n", "\r\n"], "", $chnd->nodeValue)))!==0){
                                // $chnd->nodeValue = $handleText;
                            }else{
                                $node->removeChild($chnd);
                            }
                        }
                    }

                if($handleText && $node->childNodes->length > 0){
                }
            }

            if($node && $node->childNodes && $node->childNodes->length > 0){
                $this->replaceMenuLinks($node->childNodes, $handleText);
            }
        }
    }

    private function getContentContainer(&$content_div){
        $found = false;
        $parent = null;

        foreach($content_div->childNodes as $ch){
            if($ch->nodeType <> 1){
                continue;
            }
            if(strpos(">".$ch->getAttribute("class"), "row")){
                return $content_div;
            }
            $result = $this->getContentContainer($ch);
            if(!($result===false)){
                return $result;
            }
        }
        return false;
    }

    private function movedir($source, $destination){

        if(!is_dir($destination)){
            mkdir($destination);
        }

        $i = new \DirectoryIterator($source);
        foreach($i as $f){
            if($f->isFile()){
                copy($f->getRealPath(), "$destination/" . $f->getFilename());
            }elseif(!$f->isDot() && $f->isDir()){
                $this->movedir($f->getRealPath(), "$destination/$f");
            }
        }
    }

    private function updateWelcomeFile($name){

        $welcome_file = "resources/views/bootscrape-welcome.blade.php";
        $welcome_content = file_get_contents($welcome_file);

        $st1 = strpos($welcome_content, "layouts.layout-") + 15;
        $st2 = strpos($welcome_content, '"', $st1) ;
        $oldLayout = 'layouts.layout-' . substr($welcome_content, $st1, $st2-$st1);
        $newLayout = 'layouts.layout-' . $name;
        
        if($oldLayout!==$newLayout){
            $welcome_content = str_replace($oldLayout, $newLayout, $welcome_content);
        }

        $fh = fopen($welcome_file, "w");
        fwrite($fh, $welcome_content);
        fclose($fh);
        
    }
    private function logError($msg){

    }

}
