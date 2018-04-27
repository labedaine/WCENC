/*
* ******************************************************************************
* jquery.convert.function
* file: jquery.convert.function.js
*
* *****************************************************************************
*/

/*******************************************************************************
*
* jquery.convert.function
* Author: damien
* Creation date: 03/07/13
*
******************************************************************************/
/*convert function*/

(function($){

jQuery.fn.extend({
  live: function( types, data, fn ) {
          /*if( window.console && console.warn ) {
           console.warn( "jQuery.live is deprecated. Use jQuery.on instead." );
          }*/

          jQuery( this.context ).on( types, this.selector, data, fn );
          return this;
        }
});

})(jQuery)
