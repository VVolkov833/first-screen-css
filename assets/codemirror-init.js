jQuery( document ).ready( $ => {
    const $ed = $( '#content' );
    $ed.attr( 'placeholder', `/* enter your css here */
* {
    border: 1px dotted red;
    box-sizing: border-box;
}`
    );
    wp.codeEditor.initialize( $ed, cm_settings );
    wp.codeEditor.initialize( $( '#fcpfsc-rest-css' ), cm_settings );

    console.log( wp.codeEditor.commands );
});