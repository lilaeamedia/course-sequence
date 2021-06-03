/**
 * handles editing of single row of course data
 */

import React from 'react';

class Row extends React.Component {

	constructor( props ) {
		super( props );
		this.state = { fields: this.props.fields };

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
        const elements = this.props.fields;
        console.log( elements );
		return (
			<form>
            <ul className="sequence-section-row-fields">
            </ul>
            {elements.description}

			</form>
		);
	}
}

export default Row;