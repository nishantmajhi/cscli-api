/* Unit 3: Input and Output
 * Reading and writing a single character with getchar()/putchar(),
 * and the buffered equivalents getc()/putc(). */
#include <stdio.h>

int main(void) {
    char ch;

    printf("Enter a character: ");
    ch = getchar();

    printf("You entered: ");
    putchar(ch);
    putchar('\n');

    return 0;
}
