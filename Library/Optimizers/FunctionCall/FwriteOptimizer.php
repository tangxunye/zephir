<?php

/*
 +--------------------------------------------------------------------------+
 | Zephir Language                                                          |
 +--------------------------------------------------------------------------+
 | Copyright (c) 2013 Zephir Team and contributors                          |
 +--------------------------------------------------------------------------+
 | This source file is subject the MIT license, that is bundled with        |
 | this package in the file LICENSE, and is available through the           |
 | world-wide-web at the following url:                                     |
 | http://zephir-lang.com/license.html                                      |
 |                                                                          |
 | If you did not receive a copy of the MIT license and are unable          |
 | to obtain it through the world-wide-web, please send a note to           |
 | license@zephir-lang.com so we can mail you a copy immediately.           |
 +--------------------------------------------------------------------------+
*/

/**
 * FwriteOptimizer
 *
 * Optimizes calls to 'fwrite' using internal function
 */
class FwriteOptimizer
	extends OptimizerAbstract
{
	/**
	 * @param array $expression
	 * @param Call $call
	 * @param CompilationContext $context
	 * @return bool|CompiledExpression|mixed
	 * @throws CompilerException
	 */
	public function optimize(array $expression, Call $call, CompilationContext $context)
	{
		if (!isset($expression['parameters'])) {
			return false;
		}

		if (count($expression['parameters']) != 2) {
			return false;
		}

		$context->headersManager->add('kernel/file');

		/**
		 * Process the expected symbol to be returned
		 */
		$call->processExpectedReturn($context);

		$symbolVariable = $call->getSymbolVariable();
		if ($symbolVariable) {

			if ($symbolVariable->getType() != 'variable' && $symbolVariable->getType() != 'string') {
				throw new CompilerException("Returned values by functions can only be assigned to variant variables", $expression);
			}

			if ($call->mustInitSymbolVariable()) {
				$symbolVariable->initVariant($context);
			}
		}

		$resolvedParams = $call->getReadOnlyResolvedParams($expression['parameters'], $context, $expression);
		if ($symbolVariable) {
			$context->codePrinter->output('zephir_fwrite(' . $symbolVariable->getName() . ', ' . $resolvedParams[0] . ', ' . $resolvedParams[1] . ' TSRMLS_CC);');
			return new CompiledExpression('variable', $symbolVariable->getRealName(), $expression);
		} else {
			$context->codePrinter->output('zephir_fwrite(NULL, ' . $resolvedParams[0] . ', ' . $resolvedParams[1] . ' TSRMLS_CC);');
		}

		return new CompiledExpression('null', 'null', $expression);

	}
}