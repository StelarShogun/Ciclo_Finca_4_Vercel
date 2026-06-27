function t(a,n,e){return n==="prev"?"pagination-prev":n==="next"?"pagination-next":a.page!=null?`pagination-${n}-page-${a.page}`:`pagination-${n}-${e}-${a.url??"none"}`}export{t as p};
