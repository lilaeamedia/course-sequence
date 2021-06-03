/**
 * handles main program menu and rendering of program
 */
import React from 'react';
import './App.scss';
import Section from './Section';
import base64 from 'base-64';

const AJAX_BASE  = window.wpApiSettings.root + window.wpApiSettings.wprb_ajax_base;
class App extends React.Component {
	constructor() {
		super();

		//this.saveItem = this.saveItem.bind( this );
		this.getPrograms = this.getPrograms.bind( this );
		this.handleProgramSelect = this.handleProgramSelect.bind( this );

		// initial state
		this.state = {
			programs: {},
            sequence: {},
            selected: 0,
		};
	}

	getPrograms() {
		window.jQuery.ajax({
			url: AJAX_BASE + '/programs',
			dataType: 'json',
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', window.wpApiSettings.nonce );
			},
			success: function(data) {
				this.setState( { programs: data } );
			}.bind(this)
		  });
	}

	getSequence( program ) {
        var url = AJAX_BASE + '/sequence/' + program;
        console.log( 'fetching sequence', url )
		window.jQuery.ajax({
			url: url,
			dataType: 'json',
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', window.wpApiSettings.nonce );
			},
			success: function(data) {
				this.setState( { sequence: data } );
			}.bind(this)
		  });
	}

    componentDidMount() {
		this.getPrograms();
	}


    handleProgramSelect( e ){
        e.preventDefault();
        console.log( 'handleProgramSelect change event', e.target.value );
        this.getSequence( e.target.value );
    }
    
	saveItem( key ) {
		const val = this.state.programs[ key ];
		const post_data = {
			key: key,
			value: val
		};

		window.jQuery.ajax({
			url: AJAX_BASE + `/record/${key}`,
			dataType: 'json',
			method: 'POST',
			data: JSON.stringify( post_data ),
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', window.wpApiSettings.nonce );
			},
			success: function(data) {
				if ( true === data ) {
					const saved = this.state.saved;
					saved[ key ] = true;
					this.setState( { saved } );
		
					//HACK to hide 'saved' checkmark
					setTimeout( () => {
						saved[ key ] = false;
						this.setState( { saved } );
					}, 1200 );
				};
			}.bind(this)
			
		  });
	}

	render() {
		const items = Object.keys( this.state.programs ).map( key =>
			<option key={key} value={key}>
            {this.state.programs[ key ]}
            </option>
		);
        const elements = this.state.sequence;
		return (
			<div className="course-sequence"><form id="program_select">
				<select id="program_select_menu" name="program" onChange={this.handleProgramSelect}>
					{items}
				</select>
			</form>
            { elements.length ? this.renderSections( elements ) : '' }
            </div>
		);
	}
    renderSections( elements ){
        return ( <ul className="sequence-sections">
        {

         elements.map((value, index) => {
            console.log( value, index );
            return <li key={index}><Section section_id={value.section_id} rows={value.rows} /></li>
        })}
        </ul> )
    }
    
}
export default App;
