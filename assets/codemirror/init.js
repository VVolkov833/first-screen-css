jQuery( document ).ready( $ => {

    const add_editor = textarea => {

        const placeholder = `/* enter your css here */`;//+`\n* {\n    border: 1px dotted red;\n    box-sizing: border-box;\n}`;
        const $editor = $( textarea );
    
        $editor.attr( 'placeholder', placeholder );
    
        const editor = CodeMirror.fromTextArea( $editor[0], {
            lineNumbers: true,
            styleActiveLine: true,
            mode: 'css',
            theme: 'default',
            indentUnit: 4,
            indentWithTabs: true,
            inputStyle: 'contenteditable',
            lineWrapping: true,
        });

        const $format_button = $( '<button name="format" class="button button-small" type="button" title="Format selection or whole content">{ }</button>' );
        $format_button.click( () => {
            format( editor );
        })
        $editor.after( $format_button );

        const $linewrap_button = $( '<button name="linewrap" class="button button-small" type="button" title="Visually Break / Unbreak the long lines in the editor">&nbsp;&#8626;&nbsp;</button>' );
        $linewrap_button.click( () => {
            const lineWrapping = editor.getOption( 'lineWrapping' );
            editor.setOption( 'lineWrapping', !lineWrapping );
        })
        $editor.after( $linewrap_button );

        const $infinity_button = $( '<button name="infinity" class="button button-small" type="button" title="Fit the editor to the content height">&nbsp;&#8597;&nbsp;</button>' );
        const editor_height = editor.getOption( 'viewportMargin' ); // 10
        const textarea_height = $editor.css( 'height' ); // 300px
        $infinity_button.click( () => {
            const viewportMargin = editor.getOption( 'viewportMargin' );
            const infinity_on = viewportMargin === Infinity;
            editor.setOption( 'viewportMargin', infinity_on ? editor_height : Infinity );
            editor.display.wrapper.style.height = infinity_on ? textarea_height : 'auto';
        })
        $editor.after( $infinity_button );

        return editor;
    };

    add_editor( '#content' );
    add_editor( '#fcpfsc-rest-css' );

    const format = editor => {

        const selection = editor.getSelection();

        // active selection
        if ( selection.length > 0 ) {
            editor.autoFormatRange(
                editor.getCursor( true ),
                editor.getCursor( false )
            );
            editor.focus();
            return;
        }

        // no selection
        const total_lines = editor.lineCount();
        editor.autoFormatRange(
            { line: 0, ch: 0 },
            { line: total_lines }
        );

        //editor.setCursor({ line: 0, ch: 0 }); // Place cursor at the start
        //editor.setCursor({ line: total_lines - 1, ch: editor.getLine(total_lines - 1).length }); // Place cursor at the end
        //editor.setSelection({ line: 0, ch: 0 });
        editor.focus();
    };

});
