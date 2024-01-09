<?php

class Prototype_helper{

   public $_js="";
   public $_divid="";
   public $_el;
   public $iframe=false;

   function __construct(){
      $this->iframe=$_GET['xorciframe'];
      if(!$this->iframe) header("Content-Type: text/javascript");
   }

   function replace_html($id, $html){
      $this->element_do("update(\"".escape_js($html)."\")", $id);
   }

   function hide($ids="-"){
      XorcApp::$inst->log("HIDE $ids");
      $this->element_do("hide()", $ids);
   }

   function show($ids){
      $this->element_do("show()", $ids);
   }

   function toggle($ids){
      $this->element_do("toggle()", $ids);
   }

   function remove_class($ids, $clas){
      $this->element_do("removeClassName('$clas')", $ids);
   }

   function add_class($ids, $clas){
      $this->element_do("addClassName('$clas')", $ids);
   }

   function element_do($sth, $ids="-"){
      if($ids=='-'){
         $this->_js.="el.$sth;\n";
      }else{
         if(!is_array($ids)) $ids=[$ids];
         foreach($ids as $id){
            $this->_js.="\$('$id').$sth;\n";
         }
      }
   }

   function effect($name, $id, $opts=[]){
      if($id=="-") $id="el";
      else $id="'$id'";
      $this->_js.="new Effect.".ucfirst((string) $name)."($id, ".js_options($opts).");\n";
   }

   function redirect($url){
      $this->_js.="window.location.href='".url($url)."';\n";
   }

   function alert($html){
      $this->_js.="alert('".escape_js($html)."');\n";
   }

   function select($q){
      $e=new Prototype_helper_Element;
      $e->mother=$this;
      $e->id="-";
      $e->_js="\$\$('$q')";
      return $e;
   }

   function cmd($js){
      $this->_js.=$js."\n";
   }

   function send(){
      print $this->return_string();
   }

   function return_string(){
      $pfx=$sfx="";
      if($this->iframe){
         $pfx="<html><head></head><body><script>\nwith(parent){\n";
         $sfx="}\n</script></body></html>";
      }
      return sprintf("%stry{\n%s\n} catch(e){ alert(%s);\n alert('%s');\n throw e;}\n%s",
         $pfx,
         $this->_js, "'PJS error:\\n\\n' + e.toString()", escape_js($this->_js),
         $sfx);
   }

   function _lastdiv($div=null){
      static $id;
      if($div===null) return $id;
      $id=$div;
   }

   function __get($m){
      XorcApp::$inst->log("__GET $m");
      $e=new Prototype_helper_Element;
      $e->mother=$this;
      $e->id=$m;
      return $e;
      // print_r(func_get_args());
      // call_user_func_array(array($this, $m), $args);
   }

   function __call($m, $args){
      XorcApp::$inst->log("__CALL $m".var_export($args, true));
      array_unshift($args, $m); 
      call_user_func_array($this->effect(...), $args);
   }
}

class Prototype_helper_Element implements Iterator{
   public $mother=false;
   public $id='-';
   public $_els=[];
   public $_js;
   public $run=true;

   function __call($m, $args=[]){
      XorcApp::$inst->log("PTID__CALL $m: ".var_export($args, true));
      array_unshift($args, $this->id); 
      call_user_func_array([&$this->mother, $m], $args);
   }

   public function rewind(){
      XorcApp::$inst->log("REWIND");
      $this->id='-';
      $this->run=true;
      $this->mother->cmd($this->_js.".each(function(el){");
   }

   public function current(){return $this;}

   public function key(){return "dummy";}

   public function next(){
      $this->run=!$this->run;
      $this->mother->cmd("})");
   }

   public function valid(){return $this->run;}

}


/*

module GeneratorMethods
          def to_s #:nodoc:
            returning javascript = @lines * $/ do
              if ActionView::Base.debug_rjs
                source = javascript.dup
                javascript.replace "try {\n#{source}\n} catch (e) "
                javascript << "{ alert('RJS error:\\n\\n' + e.toString()); alert('#{source.gsub(/\r\n|\n|\r/, "\\n").gsub(/["']/) { |m| "\\#{m}" }}'); throw e }"
              end
            end
          end

          # Returns a element reference by finding it through +id+ in the DOM. This element can then be
          # used for further method calls. Examples:
          #
          #   page['blank_slate']                  # => $('blank_slate');
          #   page['blank_slate'].show             # => $('blank_slate').show();
          #   page['blank_slate'].show('first').up # => $('blank_slate').show('first').up();
          def [](id)
            JavaScriptElementProxy.new(self, id)
          end

          # Returns a collection reference by finding it through a CSS +pattern+ in the DOM. This collection can then be
          # used for further method calls. Examples:
          #
          #   page.select('p')                      # => $$('p');
          #   page.select('p.welcome b').first      # => $$('p.welcome b').first();
          #   page.select('p.welcome b').first.hide # => $$('p.welcome b').first().hide();
          # 
          # You can also use prototype enumerations with the collection.  Observe:
          # 
          #   page.select('#items li').each do |value|
          #     value.hide
          #   end 
          #   # => $$('#items li').each(function(value) { value.hide(); });
          #
          # Though you can call the block param anything you want, they are always rendered in the 
          # javascript as 'value, index.'  Other enumerations, like collect() return the last statement:
          # 
          #   page.select('#items li').collect('hidden') do |item|
          #     item.hide
          #   end
          #   # => var hidden = $$('#items li').collect(function(value, index) { return value.hide(); });
          def select(pattern)
            JavaScriptElementCollectionProxy.new(self, pattern)
          end

          # Inserts HTML at the specified +position+ relative to the DOM element
          # identified by the given +id+.
          # 
          # +position+ may be one of:
          # 
          # <tt>:top</tt>::    HTML is inserted inside the element, before the 
          #                    element's existing content.
          # <tt>:bottom</tt>:: HTML is inserted inside the element, after the
          #                    element's existing content.
          # <tt>:before</tt>:: HTML is inserted immediately preceeding the element.
          # <tt>:after</tt>::  HTML is inserted immediately following the element.
          #
          # +options_for_render+ may be either a string of HTML to insert, or a hash
          # of options to be passed to ActionView::Base#render.  For example:
          #
          #   # Insert the rendered 'navigation' partial just before the DOM
          #   # element with ID 'content'.
          #   insert_html :before, 'content', :partial => 'navigation'
          #
          #   # Add a list item to the bottom of the <ul> with ID 'list'.
          #   insert_html :bottom, 'list', '<li>Last item</li>'
          #
          def insert_html(position, id, *options_for_render)
            insertion = position.to_s.camelize
            call "new Insertion.#{insertion}", id, render(*options_for_render)
          end

          # Replaces the inner HTML of the DOM element with the given +id+.
          #
          # +options_for_render+ may be either a string of HTML to insert, or a hash
          # of options to be passed to ActionView::Base#render.  For example:
          #
          #   # Replace the HTML of the DOM element having ID 'person-45' with the
          #   # 'person' partial for the appropriate object.
          #   replace_html 'person-45', :partial => 'person', :object => @person
          #
          def replace_html(id, *options_for_render)
            call 'Element.update', id, render(*options_for_render)
          end

          # Replaces the "outer HTML" (i.e., the entire element, not just its
          # contents) of the DOM element with the given +id+.
          #
          # +options_for_render+ may be either a string of HTML to insert, or a hash
          # of options to be passed to ActionView::Base#render.  For example:
          #
          #   # Replace the DOM element having ID 'person-45' with the
          #   # 'person' partial for the appropriate object.
          #   replace_html 'person-45', :partial => 'person', :object => @person
          #
          # This allows the same partial that is used for the +insert_html+ to
          # be also used for the input to +replace+ without resorting to
          # the use of wrapper elements.
          #
          # Examples:
          #
          #   <div id="people">
          #     <%= render :partial => 'person', :collection => @people %>
          #   </div>
          #
          #   # Insert a new person
          #   page.insert_html :bottom, :partial => 'person', :object => @person
          #
          #   # Replace an existing person
          #   page.replace 'person_45', :partial => 'person', :object => @person
          #
          def replace(id, *options_for_render)
            call 'Element.replace', id, render(*options_for_render)
          end

          # Removes the DOM elements with the given +ids+ from the page.
          def remove(*ids)
            record "#{javascript_object_for(ids)}.each(Element.remove)"
          end

          # Shows hidden DOM elements with the given +ids+.
          def show(*ids)
            call 'Element.show', *ids
          end

          # Hides the visible DOM elements with the given +ids+.
          def hide(*ids)
            call 'Element.hide', *ids
          end

          # Toggles the visibility of the DOM elements with the given +ids+.
          def toggle(*ids)
            call 'Element.toggle', *ids
          end

          # Displays an alert dialog with the given +message+.
          def alert(message)
            call 'alert', message
          end

          # Redirects the browser to the given +location+, in the same form as
          # +url_for+.
          def redirect_to(location)
            assign 'window.location.href', @context.url_for(location)
          end

          # Calls the JavaScript +function+, optionally with the given 
          # +arguments+.
          def call(function, *arguments)
            record "#{function}(#{arguments_for_call(arguments)})"
          end

          # Assigns the JavaScript +variable+ the given +value+.
          def assign(variable, value)
            record "#{variable} = #{javascript_object_for(value)}"
          end

          # Writes raw JavaScript to the page.
          def <<(javascript)
            @lines << javascript
          end

          # Executes the content of the block after a delay of +seconds+. Example:
          #
          #   page.delay(20) do
          #     page.visual_effect :fade, 'notice'
          #   end
          def delay(seconds = 1)
            record "setTimeout(function() {\n\n"
            yield
            record "}, #{(seconds * 1000).to_i})"
          end

          # Starts a script.aculo.us visual effect. See 
          # ActionView::Helpers::ScriptaculousHelper for more information.
          def visual_effect(name, id = nil, options = {})
            record @context.send(:visual_effect, name, id, options)
          end

          # Creates a script.aculo.us sortable element. Useful
          # to recreate sortable elements after items get added
          # or deleted.
          # See ActionView::Helpers::ScriptaculousHelper for more information.
          def sortable(id, options = {})
            record @context.send(:sortable_element_js, id, options)
          end

          # Creates a script.aculo.us draggable element.
          # See ActionView::Helpers::ScriptaculousHelper for more information.
          def draggable(id, options = {})
            record @context.send(:draggable_element_js, id, options)
          end

          # Creates a script.aculo.us drop receiving element.
          # See ActionView::Helpers::ScriptaculousHelper for more information.
          def drop_receiving(id, options = {})
            record @context.send(:drop_receiving_element_js, id, options)
          end

          private
            def page
              self
            end

            def record(line)
              returning line = "#{line.to_s.chomp.gsub /\;$/, ''};" do
                self << line
              end
            end

            def render(*options_for_render)
              Hash === options_for_render.first ? 
                @context.render(*options_for_render) : 
                  options_for_render.first.to_s
            end

            def javascript_object_for(object)
              object.respond_to?(:to_json) ? object.to_json : object.inspect
            end

            def arguments_for_call(arguments)
              arguments.map { |argument| javascript_object_for(argument) }.join ', '
            end

            def method_missing(method, *arguments)
              JavaScriptProxy.new(self, method.to_s.camelize)
            end
        end
      end
*/

?>
