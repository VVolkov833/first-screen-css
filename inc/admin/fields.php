<?php

// form printing functions

namespace FCP\FirstScreenCSS;
defined( 'ABSPATH' ) || exit;


function checkboxes($a) {
    ?>
    <fieldset
        id="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>"
        class="<?php echo isset( $a->className ) ? esc_attr( $a->className ) : '' ?>"><?php

        foreach ( (array) $a->options as $k => $v ) {
            $checked = is_array( $a->value ) && in_array( $k, $a->value );
        ?><label>
            <input type="checkbox"
                name="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>[]"
                value="<?php echo esc_attr( $k ) ?>"
                <?php echo $checked ? 'checked' : '' ?>
            >
            <span><?php echo esc_html( $v ) ?></span>
        </label><?php } ?>
    </fieldset>
    <?php
}

function select($a) {
    ?>
    <select
        name="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>"
        id="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>"
        class="<?php echo isset( $a->className ) ? esc_attr( $a->className ) : '' ?>"><?php

        if ( isset( $a->placeholder ) ) { ?>
            <option value=""><?php echo esc_html( $a->placeholder ) ?></option>
        <?php } ?>

        <?php foreach ( $a->options as $k => $v ) { ?>
            <option
                value="<?php echo esc_attr( $k ) ?>"
                <?php echo isset( $a->value ) && $a->value == $k ? 'selected' : '' ?>
            ><?php echo esc_html( $v ) ?></option>
        <?php } ?>
    </select>
    <?php
}

function input($a) {
    ?>
    <input type="text"
        name="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>"
        id="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>"
        placeholder="<?php echo isset( $a->placeholder ) ? esc_attr( $a->placeholder )  : '' ?>"
        value="<?php echo isset( $a->value ) ? esc_attr( $a->value ) : '' ?>"
        class="<?php echo isset( $a->className ) ? esc_attr( $a->className ) : '' ?>"
    />
    <?php
}

function textarea($a) {
    ?>
    <textarea
        name="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>"
        id="<?php echo esc_attr( FCPFSC_PREF . $a->name ) ?>"
        rows="<?php echo isset( $a->rows ) ? esc_attr( $a->rows ) : '10' ?>" cols="<?php echo isset( $a->cols ) ? esc_attr( $a->cols ) : '50' ?>"
        placeholder="<?php echo isset( $a->placeholder ) ? esc_attr( $a->placeholder ) : '' ?>"
        class="<?php echo isset( $a->className ) ? esc_attr( $a->className ) : '' ?>"
        style="<?php echo isset( $a->style ) ? esc_attr( $a->style ) : '' ?>"
    ><?php
        echo esc_textarea( isset( $a->value ) ? $a->value : '' )
    ?></textarea>
    <?php
}