jQuery( document ).ready(function() {
  const { createApp, watch, reactive, ref } = Vue;

  var wpwOptionsRepeater = {
    setup() {
  
      let module = reactive({
        data : [],
        items : [],
      });
      
      let source_data = ref("");
  
      let event = new Event('change');
  
      watch( () => module.items, 
      (count, prevCount) => {
        source_data.value.innerHTML = JSON.stringify(module.items);
        source_data.value.dispatchEvent(event);
      },
      { deep: true }
      );
  
      module.deleteItem = function(delete_item){
        if(!confirm("Delete item?")) return false;
        module.items.forEach(function(item){
          if(item == delete_item){
            module.items.splice(module.items.indexOf(item), 1);
          }
        });
      }      
      
      module.move = function(move_item, way){
        let index = module.items.indexOf(move_item);
        let result_index = index + way;
        if(result_index < 0 || result_index > module.items.length - 1){ return; }

        module.items.forEach(function(item, index){
          if(item == move_item){
            let new_item = JSON.parse(JSON.stringify(item));
            module.items.splice(module.items.indexOf(item), 1);
            module.items.splice(result_index, 0, new_item);
          }
        });
      }
  
      module.createItem = function(props){
        props = props.split(",");
        let obj = {
          uid: Math.random()+"-"+Math.random()
        };
        props.forEach(key => {
          obj[key] = "";
        });
        module.items.splice(0, 0, reactive(obj));
        return false;
      }
  
      return {
        module,
        source_data
      }
    },
    mounted:function(){
      var vm = this;
      if(vm.source_data.value.length){
        vm.module.items = JSON.parse(vm.source_data.value);
      }
    }
  }  
  
  
  const repeaterNodes = document.querySelectorAll('[data-type="repeater"]');
  repeaterNodes.forEach(function (item) {
      const app = createApp(wpwOptionsRepeater);
      app.component('draggable', vuedraggable);
      app.mount(item);
  });
  



});

