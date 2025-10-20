#!/usr/bin/env python3
import os
import re
import sys

def check_php_syntax(file_path):
    """Basic PHP syntax checks"""
    try:
        with open(file_path, 'r') as f:
            content = f.read()
        
        errors = []
        
        # Check for unclosed braces
        open_braces = content.count('{')
        close_braces = content.count('}')
        if open_braces != close_braces:
            errors.append(f"Brace mismatch: {open_braces} opening, {close_braces} closing")
        
        # Check for unclosed parentheses in function definitions
        lines = content.split('\n')
        for i, line in enumerate(lines, 1):
            # Check for function definitions
            if re.search(r'^\s*(public|private|protected)?\s*function', line):
                # Count parentheses in this line and following lines until we find the opening brace
                paren_count = 0
                line_check = line
                j = i
                while j <= len(lines) and '{' not in line_check:
                    paren_count += line_check.count('(') - line_check.count(')')
                    j += 1
                    if j <= len(lines):
                        line_check = lines[j-1]
                
                if paren_count != 0:
                    errors.append(f"Line {i}: Possible unmatched parentheses in function definition")
        
        # Check for missing semicolons (basic check)
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped and not stripped.startswith('//') and not stripped.startswith('/*'):
                if (stripped.endswith('return') or 
                    (stripped.startswith('return ') and not stripped.endswith(';') and not stripped.endswith('{') and not stripped.endswith('['))):
                    if not any(x in stripped for x in ['{', '}', '//', '/*', '*/', '[', ']']):
                        errors.append(f"Line {i}: Missing semicolon after return statement: {stripped}")
        
        return errors
    except Exception as e:
        return [f"Error reading file: {str(e)}"]

def main():
    if len(sys.argv) > 1:
        files_to_check = sys.argv[1:]
    else:
        # Check all PHP files in app directory
        files_to_check = []
        for root, dirs, files in os.walk('app'):
            for file in files:
                if file.endswith('.php'):
                    files_to_check.append(os.path.join(root, file))
    
    all_good = True
    for file_path in files_to_check:
        errors = check_php_syntax(file_path)
        if errors:
            all_good = False
            print(f"\n{file_path}:")
            for error in errors:
                print(f"  - {error}")
    
    if all_good:
        print("✅ No syntax issues detected in checked files!")
    else:
        print("\n❌ Syntax issues found. Please review the files above.")
        return 1
    
    return 0

if __name__ == "__main__":
    sys.exit(main())