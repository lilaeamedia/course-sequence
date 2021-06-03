/**
 * handles sorting of rows in single section
 */
import React from 'react';
import Row from './Row';

class Section extends React.Component {

	constructor( props ) {
		super( props );
		this.state = { 
            section_id: this.props.section_id,
            rows: this.props.rows 
        };

		this.handleChange = this.handleChange.bind( this );
		this.handleSubmit = this.handleSubmit.bind( this );
	}

	handleChange( event ) {
		this.props.handleChange( this.props.id, event.target.value );
	}

	handleSubmit( event ) {
		event.preventDefault();
		this.props.handleSubmit( this.props.id );
	}

	render() {
        const elements = this.state.rows;
        //console.log( 'Section.render()', elements );
		return (
            <ul id={ this.state.section_id } className="sequence-section-rows">
            {elements.map((value, index) => {
                //console.log( value, index );
                return <li key={index}><Row fields={value} /></li>
            })}

            </ul>
			
		);
	}
}

export default Section;