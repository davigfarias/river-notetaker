// O CodeMirror força autocorrect/autocapitalize "off" no campo de entrada e o EasyMDE
// não repassa essas opções: no iOS isso some com a sugestão do teclado e a correção.
export function enableNativeKeyboard(editor) {
    editor.codemirror.setOption('autocorrect', true);
    editor.codemirror.setOption('autocapitalize', true);

    const input = editor.codemirror.getInputField();

    input.setAttribute('autocorrect', 'on');
    input.setAttribute('autocapitalize', 'sentences');
    input.setAttribute('spellcheck', 'true');
}
