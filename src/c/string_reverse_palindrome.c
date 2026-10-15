/* Unit 6: Arrays
 * Reading and writing strings via a character array, and checking
 * whether a string is a palindrome. */
#include <stdio.h>
#include <string.h>

int main(void) {
    char str[100];

    printf("Enter a string: ");
    fgets(str, sizeof(str), stdin);
    str[strcspn(str, "\n")] = '\0'; /* strip trailing newline */

    int len = strlen(str);
    int isPalindrome = 1;

    for (int i = 0; i < len / 2; i++) {
        if (str[i] != str[len - 1 - i]) {
            isPalindrome = 0;
            break;
        }
    }

    if (isPalindrome) {
        printf("\"%s\" is a palindrome\n", str);
    } else {
        printf("\"%s\" is not a palindrome\n", str);
    }

    return 0;
}
