<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
	->in([__DIR__ . '/core', __DIR__ . '/assets'])
	->name('*.php')
	->ignoreDotFiles(true)
	->ignoreVCS(true);

return (new Config())
	->setIndent("\t")
	->setRiskyAllowed(true)
	->setRules([
		// 🔹 Базовые пресеты
		'@PSR12'            => true,
		'phpdoc_to_comment' => false,   // не нужно комментировать докблоки
		'@Symfony'          => true,    // можно убрать, если строгая PSR12 нужна без лишнего

		// 🔹 Массивы
		'array_syntax'                                => ['syntax' => 'short'],   // [] вместо array()
		'no_multiline_whitespace_around_double_arrow' => true,
		'normalize_index_brace'                       => true,
		'trim_array_spaces'                           => true,

		// 🔹 Строки
		'single_quote' => true,
		'concat_space' => ['spacing' => 'one'],

		// 🔹 Импорты и use
		'no_unused_imports'       => true,
		'ordered_imports'         => ['sort_algorithm' => 'alpha'],
		'global_namespace_import' => [
			'import_constants' => true,
			'import_functions' => true,
			'import_classes' => null
		],

		// 🔹 Докблоки
		'phpdoc_align'           => ['align' => 'vertical'],
		'phpdoc_order'           => true,
		'phpdoc_trim'            => true,
		'phpdoc_no_empty_return' => true,
		'phpdoc_scalar'          => true,
		'phpdoc_types'           => true,

		// 🔹 Классы и функции
		'class_attributes_separation' => ['elements' => ['method' => 'one']],
		'method_argument_space'       => ['on_multiline' => 'ensure_fully_multiline'],
		'function_typehint_space'     => true,
		'no_empty_statement'          => true,
		'return_type_declaration'     => ['space_before' => 'none'],
		'single_line_throw'           => false,                                          // не всегда удобно, можно отключить

		// 🔹 Стиль
		'binary_operator_spaces' => [
			'default'   => 'single_space',
			'operators' => [
				'=>' => 'align_single_space_minimal',
				'='  => 'align_single_space_minimal',
			],
		],
		'blank_line_after_namespace'   => true,
		'blank_line_after_opening_tag' => true,
		'blank_line_before_statement'  => ['statements' => ['return']],
		'cast_spaces'                  => true,
		'declare_equal_normalize'      => ['space' => 'single'],
		'lowercase_keywords'           => true,
		'no_trailing_whitespace'       => true,
		'no_whitespace_in_blank_line'  => true,
		'single_blank_line_at_eof'     => true,
		'strict_param'                 => true,                           // risky

		// 🔹 Разное
		'native_function_invocation' => ['include' => ['@all']],   // risky
		'combine_consecutive_issets' => true,
		'combine_consecutive_unsets' => true,
		'simplified_null_return'     => true,
	])
	->setFinder($finder);
