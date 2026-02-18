
// view-controls.js - cambia entre vista tabla y grilla
class ViewControls {
  constructor({ container, defaultView='table', onViewChange } = {}){
    this.container = document.querySelector(container);
    this.view = defaultView;
    this.onViewChange = onViewChange || function(){};
    this.bind();
  }
  bind(){
    const buttons = document.querySelectorAll(".view-btn");
    buttons.forEach(btn => {
      btn.addEventListener("click", ()=>{
        buttons.forEach(b=> b.classList.remove("active"));
        btn.classList.add("active");
        this.view = btn.dataset.view;
        if(this.onViewChange) this.onViewChange(this.view);
      });
    });
  }
}
window.ViewControls = ViewControls;
