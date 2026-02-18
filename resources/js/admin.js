
// admin.js - pequeñas ayudas generales
document.addEventListener("DOMContentLoaded", ()=>{
  const toggle = document.querySelector(".toggle-sidebar");
  const aside = document.querySelector(".admin-sidebar");
  if(toggle && aside){
    toggle.addEventListener("click", ()=> aside.classList.toggle("collapsed"));
  }
});
