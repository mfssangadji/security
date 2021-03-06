<?php
/**
 * This file is part of the O2System PHP Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author         Mohamad Rafi Randoni
 * @copyright      Copyright (c) Steeve Andrian Salim
 */
// ------------------------------------------------------------------------

namespace O2System\Security;

use O2System\Security\Filters\Validation;
use O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException;
use O2System\Spl\Exceptions\Logic\InvalidArgumentException;
use O2System\Spl\Exceptions\Logic\OutOfRangeException;

/**
 * Class Rules
 *
 * @package O2System\Security
 */
class Rules
{
    /**
     * Validation Rules
     *
     * @access  protected
     * @type    array
     */
    protected $rules = [];

    /**
     * Validation Errors
     *
     * @access  protected
     * @type    array
     */
    protected $errors = [];

    /**
     * Validation Messages
     *
     * @access  protected
     * @type    array
     */
    protected $customErrors = [];

    /**
     * Source Variables
     *
     * @access  protected
     * @type    array
     */
    protected $sourceVars = [];

    // ------------------------------------------------------------------------

    public function __construct( $sourceVars = [] )
    {
        $this->customErrors = [
            'required'  => ':attribute is required',
            'float'     => ':attribute data format should be float',
            'email'     => ':attribute not a valid email format',
            'integer'   => ':attribute should be an integer',
            'minLength' => ':attribute should be more than :params',
            'maxLength' => ':attribute should be less than :params',
            'listed'    => ':attribute not listed in :params',
        ];

        if ( ! empty( $sourceVars ) ) {
            if ( $sourceVars instanceof \ArrayObject ) {
                $sourceVars = $sourceVars->getArrayCopy();
            }

            $this->sourceVars = $sourceVars;
        }
    }

    /**Validation::setSource
     *
     * @param array $sourceVars
     *
     * @access  public
     */
    public function setSource( array $sourceVars )
    {
        $this->sourceVars = $sourceVars;
    }

    // --------------------------------------------------------------------------------------

    /**
     * Add source
     *
     * @param string $key
     * @param string $value
     */
    public function addSource( $key, $value )
    {
        $this->sourceVars[ $key ] = $value;
    }

    // --------------------------------------------------------------------

    /**
     * Validation::addRules
     *
     * @param array $rules
     */
    public function addRules( array $rules )
    {
        foreach ( $rules as $rule ) {
            $this->addRule( $rule[ 'field' ], $rule[ 'label' ], $rule[ 'rules' ], $rule[ 'messages' ] );
        }
    }

    // --------------------------------------------------------------------

    /**
     * Validation::addRule
     *
     * @param       $field
     * @param       $label
     * @param       $rules
     * @param array $messages
     */
    public function addRule( $field, $label, $rules, $messages = [] )
    {
        $this->rules[ $field ] = [
            'field'    => $field,
            'label'    => $label,
            'rules'    => $rules,
            'messages' => $messages,
        ];
    }

    // --------------------------------------------------------------------

    /**
     * Validation::hasRule
     *
     * @param $field
     *
     * @return bool
     */
    public function hasRule( $field )
    {
        if ( array_key_exists( $field, $this->rules ) ) {
            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Validation::setMessage
     *
     * @param $field
     * @param $message
     */
    public function setMessage( $field, $message )
    {
        $this->customErrors[ $field ] = $message;
    }

    // ------------------------------------------------------------------------

    public function validate()
    {
        /* Check if data source is existed or not */
        if ( count( $this->sourceVars ) < 1 OR empty( $this->sourceVars ) ) {
            throw new InvalidArgumentException( 'E_HEADER_INVALIDARGUMENTEXCEPTION', 1 );
        }

        foreach ( $this->rules as $field => $rule ) {

            /* Throw exception if existed rules field not yet exist in data source */
            if ( ! array_key_exists( $field, $this->sourceVars ) ) {
                throw new OutOfRangeException( 'E_HEADER_OUTOFRANGEEXCEPTION', 1 );
            }

            if ( is_string( $rule[ 'rules' ] ) ) {
                /* Explode rules by | as delimiter */
                $xRule = explode( '|', $rule[ 'rules' ] );

                foreach ( $xRule as $method ) {
                    $validationClass = new Validation;

                    /* Get parameter from given data */
                    $methodParams = $this->sourceVars[ $field ];
                    if ( ! is_array( $methodParams ) ) {
                        $methodParams = [ $methodParams ];
                    }

                    if ( empty( $methodParams ) ) {
                        array_unshift( $methodParams, null );
                    }

                    /* Check if rules has parameter */
                    if ( preg_match_all( "/\[(.*)\]/", $method, $ruleParams ) ) {

                        /* Remove [] from method */
                        $method = preg_replace( "/\[.*\]/", '', $method );

                        /* Explode rule parameter */
                        $ruleParams = explode( ',', preg_replace( "/,[ ]+/", ',', $ruleParams[ 1 ][ 0 ] ) );

                        /* Merge method's param with rule's param */
                        $methodParams = array_merge( $methodParams, $ruleParams );
                    }

                    $method = 'is' . studlycase( $method );

                    /* Throw exception if method not exists in validation class */
                    if ( ! method_exists( $validationClass, $method ) ) {
                        throw new BadMethodCallException( 'E_HEADER_BADMETHODCALLEXCEPTION', 1 );
                    }

                    $validate = call_user_func_array( [ &$validationClass, $method ], $methodParams );

                    if ( ! $validate ) {
                        /* Reverse method name to lower case */
                        $methodName = lcfirst( str_replace( 'is', '', $method ) );

                        if ( ! empty( $rule[ 'messages' ] ) ) {
                            $message = $rule[ 'messages' ];

                            /* If $rule message is array, replace $message with specified message */
                            if ( is_array( $rule[ 'messages' ] ) ) {
                                if ( isset( $rule[ 'messages' ][ $methodName ] ) ) {
                                    $message = $rule[ 'messages' ][ $methodName ];
                                } else {
                                    $message = $rule[ 'messages' ][ $field ];
                                }
                            }
                        } elseif ( array_key_exists( $field, $this->customErrors ) ) {
                            $message = $this->customErrors[ $field ];
                        } elseif ( array_key_exists( $methodName, $this->customErrors ) ) {
                            $message = $this->customErrors[ $methodName ];
                        } else {
                            $message = strtoupper( 'is_' . join( '', explode( 'is', $method ) ) );
                        }

                        /* Replace message placeholder, :attribute, :params */
                        $message = str_replace( ':attribute', $field, $message );
                        if ( isset( $ruleParams ) AND ! empty( $ruleParams[ 0 ] ) ) {
                            $message = str_replace( ':params', implode( ',', $ruleParams ), $message );
                        }

                        $this->setError( $message, $methodParams );
                    }

                }
            }
        }

        return empty( $this->errors ) ? true : false;
    }

    // --------------------------------------------------------------------------------------

    /**
     * Validation::setError
     *
     * @param       $error
     * @param array $vars
     */
    protected function setError( $error, $vars = [] )
    {
        if ( array_key_exists( $error, $this->customErrors ) ) {
            $error = $this->customErrors[ $error ];
        } else {
            language()->loadFile( 'validation' );
            $line = language()->getLine( $error );

            if ( ! empty( $line ) ) {
                $error = $line;
            }
        }

        array_unshift( $vars, $error );
        $this->errors[] = call_user_func_array( 'sprintf', $vars );
    }

    // ------------------------------------------------------------------------

    /**
     * Validation::getErrors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}